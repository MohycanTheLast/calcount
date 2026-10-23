<?php

namespace App\Service;

use App\Entity\Security\User;
use App\Model\DietRecord;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpClient\NativeHttpClient;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;



class OpenAiClient
{
    const API_URL = 'https://api.openai.com/v1/responses';

    // Odhad z textu i rozpoznání fotky. gpt-6-luna je oficiální náhrada za gpt-4.1-mini a gpt-4o-mini
    // (ty OpenAI vypíná 14. 1. 2027) a vyjde zhruba 8× levněji – viz docs/ai-models.md.
    const MODEL = 'gpt-6-luna';

    // Bez uvažování (výchozí je „medium“) – úkol je jednoduchý a tokeny uvažování se účtují jako výstup
    const REASONING_EFFORT = 'none';

    // Detail obrázku vždy výslovně: u nových modelů znamená „auto“ plné rozlišení (fotka z iPhonu ~14 000 tokenů),
    // „high“ zmenší fotku na max. 2 500 políček 32×32 px (~3 000 tokenů)
    const IMAGE_DETAIL = 'high';

    public function __construct(
        protected HttpClientInterface $httpClient,
        protected Security $security,
        protected string $openAiApiKey,
    )
    {
        $this->httpClient = new NativeHttpClient(); // PHP Stream
    }

    /**
     * @throws \Throwable
     */
    public function analyzeImage(string $base64Image, string $mimeType): string
    {
        return $this->requestOutputText([
            'model' => self::MODEL,
            'reasoning' => ['effort' => self::REASONING_EFFORT],
            'input' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'input_image',
                            'image_url' => "data:{$mimeType};base64,{$base64Image}",
                            'detail' => self::IMAGE_DETAIL,
                        ],
                        [
                            'type' => 'input_text',
                            'text' => 'Co vidíš na fotce za jídlo nebo pití? Odpověz stručně česky – vypiš jen název jídla/nápoje a přibližné množství, jako by sis to chtěl zapsat do kalorického deníčku. Např: "Svíčková s 5 knedlíky, pivo 0.5 l"',
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @throws \Throwable
     */
    public function estimateNutrition(string $text): DietRecord
    {
//        /** @var User $user */
        $user = $this->security->getUser();
        $settings = $user->getNewOrExistSettings();
        $commentMode = $settings->getAiCommentMode();

//        $commentMode = "funny";

        if ($commentMode === "random") {
            $modes = ["none", "brief_health", "funny", "praise_or_criticize"];
            $commentMode = $modes[array_rand($modes)];
        }

        $prompt = "Jsi nutriční asistent. Z textu odhadni kalorickou hodnotu jídla a pitný režim pokud obsahuje text i pití. Vrať pouze validní JSON.";
        switch ($commentMode) {
            case "brief_health":
                $prompt .= " Součástí odpovědi bude stručný komentář, zda je jídlo nebo pití z textu zdravé nebo ne.";
                break;
            case "funny":
                $prompt .= " Součástí odpovědi bude vtipný komentář k textu.";
                break;
            case "praise_or_criticize":
                $prompt .= " Součástí odpovědi bude pochvala nebo kritika za zkonzumované jídlo/pití z textu. ";
                break;
            default:
                break;
        }

        $jsonProperties = [
            'kcal' => ['type' => 'number'],
            'waterInMl' => ['type' => 'number']
        ];

        $required = ['kcal', 'waterInMl'];

        if ($commentMode !== "none") {
            $jsonProperties['comment'] = ['type' => 'string'];
            $required[] = "comment";
        }

        $json = $this->requestOutputText([
            'model' => self::MODEL,
            'reasoning' => ['effort' => self::REASONING_EFFORT],
            'input' => [
                ['role' => 'system', 'content' => $prompt],
                ['role' => 'user', 'content' => $text],
            ],
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'ai_output_dto',
                    'schema' => [
                        'type' => 'object',
                        'properties' => $jsonProperties,
                        'required' => $required,
                        "additionalProperties" => false
                    ],
                    'strict' => true
                ]
            ]
        ]);

        $serializer = new Serializer(
            [new ObjectNormalizer()],
            [new JsonEncoder()]
        );

        return $serializer->deserialize($json, DietRecord::class, 'json');
    }

    /**
     * Zavolá Responses API a vrátí text odpovědi modelu.
     *
     * Chyba API nebo prázdná odpověď vyhodí výjimku (ApiController ji zaloguje a uživateli vrátí obecnou hlášku)
     * – dřív se při chybě tiše uložil záznam s 0 kcal.
     *
     * @throws \RuntimeException
     */
    private function requestOutputText(array $body): string
    {
        $response = $this->httpClient->request('POST', self::API_URL, [
            'auth_bearer' => $this->openAiApiKey,
            'headers' => [
                'Content-Type: application/json',
            ],
            'body' => json_encode($body),
        ]);

        $status = $response->getStatusCode();
        $data = $response->toArray(false);

        if ($status >= 400) {
            throw new \RuntimeException(sprintf('OpenAI API error %d: %s', $status, $data['error']['message'] ?? 'unknown'));
        }

        // Odpověď je pole položek; u modelů s uvažováním může před zprávou být položka „reasoning“
        foreach ($data['output'] ?? [] as $item) {
            if (($item['type'] ?? null) !== 'message') {
                continue;
            }

            foreach ($item['content'] ?? [] as $content) {
                if (($content['type'] ?? null) === 'output_text' && ($content['text'] ?? '') !== '') {
                    return $content['text'];
                }
            }
        }

        throw new \RuntimeException(sprintf(
            'OpenAI API returned no text (status: %s, incomplete reason: %s)',
            $data['status'] ?? 'unknown',
            $data['incomplete_details']['reason'] ?? '-'
        ));
    }
}

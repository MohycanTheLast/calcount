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
        $options = [
            'auth_bearer' => $this->openAiApiKey,
            'headers' => [
                'Content-Type: application/json',
            ],
            'body' => json_encode([
                'model' => 'gpt-4o-mini',
                'input' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'input_image',
                                'image_url' => "data:{$mimeType};base64,{$base64Image}",
                            ],
                            [
                                'type' => 'input_text',
                                'text' => 'Co vidíš na fotce za jídlo nebo pití? Odpověz stručně česky – vypiš jen název jídla/nápoje a přibližné množství, jako by sis to chtěl zapsat do kalorického deníčku. Např: "Svíčková s 5 knedlíky, pivo 0.5 l"',
                            ],
                        ],
                    ],
                ],
            ]),
        ];

        $result = $this->httpClient->request('POST', self::API_URL, $options);
        $output = $result->toArray(false);

        return $output['output'][0]['content'][0]['text'] ?? '';
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

        $aiOptions = [
            'auth_bearer' => $this->openAiApiKey,
            'headers' => [
                'Content-Type: application/json',
            ],
            'body' => json_encode([
                'model' => 'gpt-4.1-mini',
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
            ])
        ];

        $aiResult = $this->httpClient->request("POST", self::API_URL, $aiOptions);

        $aiOutput = $aiResult->toArray(false);

        // TODO errors

        $json = $aiOutput['output'][0]['content'][0]['text'] ?? '{"kcal":0,"waterInMl":0}';

        $serializer = new Serializer(
            [new ObjectNormalizer()],
            [new JsonEncoder()]
        );

        return $serializer->deserialize($json, DietRecord::class, 'json');
    }
}

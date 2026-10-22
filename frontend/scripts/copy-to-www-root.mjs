// Zkopíruje výstup buildu (.output/public) do backend/www-root.
// Nitro výstupní složku před buildem maže, proto se nebuildí přímo do www-root –
// smazalo by to index.php, .htaccess, manifest a ikony.
import { cpSync, existsSync, rmSync } from 'node:fs'
import { dirname, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'

const frontendDir = resolve(dirname(fileURLToPath(import.meta.url)), '..')
const src = resolve(frontendDir, '.output/public')
const dest = resolve(frontendDir, '../backend/www-root')

if (!existsSync(src)) {
    console.error(`Výstup buildu nenalezen: ${src}`)
    process.exit(1)
}

// _nuxt obsahuje soubory s hashem v názvu – staré verze by se jinak hromadily
rmSync(resolve(dest, '_nuxt'), { recursive: true, force: true })

cpSync(src, dest, { recursive: true })

console.log(`Build zkopírován do ${dest}`)

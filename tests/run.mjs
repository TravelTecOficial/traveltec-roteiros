// Roda os testes PHP em PHP 7.4 e 8.2 (php-wasm, sem instalar PHP nem WordPress).
// Uso: node run.mjs [parte-do-nome-do-arquivo]   |   PHP_VERSOES=8.2 node run.mjs
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { PHP } from '@php-wasm/universal';
import { loadNodeRuntime } from '@php-wasm/node';

const AQUI = path.dirname(fileURLToPath(import.meta.url));
const PLUGIN = process.env.PLUGIN_DIR || path.resolve(AQUI, '../traveltec-roteiros');
const VERSOES = (process.env.PHP_VERSOES || '7.4,8.2').split(',');
const filtro = process.argv[2] || '';
const testes = fs.readdirSync(path.join(AQUI, 'php')).filter((f) => f.endsWith('.test.php') && f.includes(filtro));
if (!testes.length) {
  console.error('nenhum teste encontrado');
  process.exit(1);
}

let falhas = 0;
for (const versao of VERSOES) {
  console.log(`\n===== PHP ${versao} =====`);
  for (const arq of testes) {
    const php = new PHP(await loadNodeRuntime(versao, { emscriptenOptions: { processId: 1 } }));
    php.mkdir('/p');
    php.mkdir('/p/includes');
    php.mkdir('/t');
    for (const f of fs.readdirSync(path.join(PLUGIN, 'includes'))) {
      php.writeFile(`/p/includes/${f}`, fs.readFileSync(path.join(PLUGIN, 'includes', f)));
    }
    php.writeFile('/p/traveltec-roteiros.php', fs.readFileSync(path.join(PLUGIN, 'traveltec-roteiros.php')));
    php.mkdir('/p/assets');
    for (const f of fs.readdirSync(path.join(PLUGIN, 'assets')).filter((n) => /\.(js|css)$/.test(n))) {
      php.writeFile(`/p/assets/${f}`, fs.readFileSync(path.join(PLUGIN, 'assets', f)));
    }
    php.writeFile('/t/bootstrap.php', fs.readFileSync(path.join(AQUI, 'php', 'bootstrap.php')));
    php.writeFile(`/t/${arq}`, fs.readFileSync(path.join(AQUI, 'php', arq)));
    let r;
    try {
      r = await php.run({ code: `<?php require '/t/${arq}'; echo "\\nFALHAS=" . $GLOBALS['tt_falhas'];` });
    } catch (e) {
      const msg = (e.response && (e.response.text || e.response.errors)) || e.message;
      console.log(`--- ${arq}\nERRO FATAL DO PHP:\n${String(msg).split('\n').slice(0, 6).join('\n')}`);
      falhas++;
      continue;
    }
    process.stdout.write(`--- ${arq}\n${r.text}\n`);
    if (r.errors) console.log('stderr:', r.errors);
    const m = /FALHAS=(\d+)/.exec(r.text);
    if (!m || Number(m[1]) > 0) falhas++;
  }
}
console.log(falhas ? `\n${falhas} ARQUIVO(S) COM FALHA` : '\nTUDO OK');
process.exit(falhas ? 1 : 0);

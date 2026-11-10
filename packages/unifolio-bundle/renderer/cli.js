import { rendrePortfolio } from './entry.js';

// Entrée : instantané JSON sur stdin. Sortie : { fichier: html } JSON sur stdout. Code ≠ 0 et message sur stderr en cas d'échec.
const lire = async () => {
  const morceaux = [];
  for await (const m of process.stdin) morceaux.push(m);

  return Buffer.concat(morceaux).toString('utf8');
};

try {
  const instantane = JSON.parse(await lire());
  process.stdout.write(JSON.stringify(await rendrePortfolio(instantane)));
} catch (e) {
  process.stderr.write(`${e?.stack ?? e}\n`);
  process.exit(1);
}

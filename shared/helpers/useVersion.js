import packageJson from '@/../package.json';


export function useVersion() {
  const version = packageJson.version;
  return { version };
}
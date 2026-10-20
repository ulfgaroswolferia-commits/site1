export const THEME = {
  primary: 'greenBright',
  secondary: 'green',
  amber: 'yellowBright',
  danger: 'redBright',
  muted: 'gray',
  cyan: 'cyanBright',
} as const;

export function renderBar(current: number, max: number, width = 10): string {
  const safeMax = Math.max(1, max);
  const ratio = Math.max(0, Math.min(1, current / safeMax));
  const filled = Math.round(ratio * width);
  return '█'.repeat(filled) + '░'.repeat(Math.max(0, width - filled));
}

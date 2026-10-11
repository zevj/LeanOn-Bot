export function getChartTheme(isDark) {
  return isDark
    ? {
        grid: 'rgba(148, 163, 184, 0.14)',
        tick: '#cbd5e1',
        tickMuted: '#94a3b8',
        tooltipBg: 'rgba(15, 23, 42, 0.96)',
        tooltipBorder: 'rgba(148, 163, 184, 0.18)',
        tooltipTitle: '#f8fafc',
        tooltipBody: '#d1fae5',
        chartBorder: '#1e2533',
        pointBorder: '#1e2533',
      }
    : {
        grid: 'rgba(0, 0, 0, 0.04)',
        tick: '#9ca3af',
        tickMuted: '#6b7280',
        tooltipBg: 'rgba(17, 24, 39, 0.95)',
        tooltipBorder: 'rgba(255, 255, 255, 0.1)',
        tooltipTitle: '#f3f4f6',
        tooltipBody: '#a3e6a0',
        chartBorder: '#ffffff',
        pointBorder: '#ffffff',
      }
}

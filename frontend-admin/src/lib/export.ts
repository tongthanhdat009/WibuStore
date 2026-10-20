function cell(v: unknown): string {
  const s = v === null || v === undefined ? '' : String(v);
  return /[",\n]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s;
}

export function downloadCSV(filename: string, headers: string[], rows: (string | number | null | undefined)[][]): void {
  const lines = [headers.map(cell).join(','), ...rows.map((r) => r.map(cell).join(','))];
  const blob = new Blob(["\uFEFF" + lines.join('\n')], { type: 'text/csv;charset=utf-8' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  a.remove();
  setTimeout(() => URL.revokeObjectURL(url), 1000);
}

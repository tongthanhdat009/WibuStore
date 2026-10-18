export function formatVND(amount: number): string {
  return new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND',
    maximumFractionDigits: 0,
  }).format(amount).replace('₫', '₫').trim();
}

export function formatNumber(num: number): string {
  return new Intl.NumberFormat('vi-VN').format(num);
}

export function truncateText(text: string, maxLength: number): string {
  if (text.length <= maxLength) return text;
  return text.slice(0, maxLength) + '...';
}

export function getStatusBadgeInfo(status: string) {
  switch (status) {
    case 'in_stock':
      return {
        label: 'CÒN HÀNG',
        english: 'IN STOCK',
        bgClass: 'bg-emerald-100 text-emerald-800 border-emerald-500',
        dotClass: 'bg-emerald-600',
      };
    case 'pre_order':
      return {
        label: 'ĐẶT TRƯỚC',
        english: 'PRE-ORDER',
        bgClass: 'bg-amber-100 text-amber-900 border-amber-500',
        dotClass: 'bg-amber-600',
      };
    case 'sold_out':
      return {
        label: 'HẾT HÀNG',
        english: 'SOLD OUT',
        bgClass: 'bg-rose-100 text-rose-800 border-rose-400',
        dotClass: 'bg-rose-600',
      };
    case 'coming_soon':
      return {
        label: 'SẮP RA MẮT',
        english: 'COMING SOON',
        bgClass: 'bg-blue-100 text-blue-800 border-blue-400',
        dotClass: 'bg-blue-600',
      };
    default:
      return {
        label: 'THÔNG BÁO',
        english: 'NOTICE',
        bgClass: 'bg-neutral-100 text-neutral-800 border-neutral-300',
        dotClass: 'bg-neutral-500',
      };
  }
}

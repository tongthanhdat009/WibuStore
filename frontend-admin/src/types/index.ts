export type OrderStatus = 'pending' | 'confirmed' | 'processing' | 'shipped' | 'delivered' | 'completed' | 'cancelled' | 'expired';
export type PaymentStatus = 'unpaid' | 'partially_paid' | 'paid' | 'refunded' | 'partially_refunded';
export type FulfillmentStatus = 'unfulfilled' | 'preparing' | 'shipped' | 'delivered' | 'returned';

export interface OrderLineItem {
  id: string;
  productId: string;
  productName: string;
  variantId?: string;
  variantName?: string;
  sku: string;
  price: number;
  quantity: number;
  thumbnail: string;
  isPreorder?: boolean;
}

export interface OrderTimelineEvent {
  time: string;
  title: string;
  description: string;
  actor?: string;
}

export interface Order {
  id: string;
  displayCode: string;
  customerName: string;
  customerEmail: string;
  customerPhone: string;
  customerAddress: string;
  items: OrderLineItem[];
  totalAmount: number;
  shippingFee: number;
  discountAmount: number;
  finalAmount: number;
  paymentStatus: PaymentStatus;
  fulfillmentStatus: FulfillmentStatus;
  orderStatus: OrderStatus;
  orderType: 'standard' | 'preorder' | 'mixed';
  paymentMethod: string;
  trackingCode?: string;
  carrier?: string;
  customerNotes?: string;
  internalNotes?: string;
  createdAt: string;
  timeline: OrderTimelineEvent[];
}

export type ProductPublishStatus = 'draft' | 'published' | 'archived';

export interface ProductVariantDetail {
  id: string;
  sku: string;
  name: string;
  optionValue: string;
  price: number;
  compareAtPrice?: number;
  stock: number;
  reserved: number;
  available: number;
  active: boolean;
  image?: string;
}

export interface AdminProduct {
  id: string;
  slug: string;
  name: string;
  vietnameseTitle: string;
  series: string;
  seriesSlug: string;
  character: string;
  manufacturer: string;
  category: 'scale' | 'nendoroid' | 'action' | 'model_kit';
  categoryName: string;
  scale?: string;
  dimensions?: string;
  material?: string;
  releaseDate: string;
  basePrice: number;
  compareAtPrice?: number;
  depositPrice?: number;
  stockStatus: 'in_stock' | 'pre_order' | 'sold_out' | 'coming_soon';
  publishStatus: ProductPublishStatus;
  stockCount: number;
  reservedCount: number;
  availableCount: number;
  lowStockThreshold: number;
  images: string[];
  coverImage: string;
  description: string;
  specifications: Record<string, string>;
  variants: ProductVariantDetail[];
  tags: string[];
  seoTitle?: string;
  seoDescription?: string;
  updatedAt: string;
  createdAt: string;
}

export interface InventoryItem {
  sku: string;
  productId: string;
  productName: string;
  variantName: string;
  thumbnail: string;
  series: string;
  warehouse: string;
  onHand: number;
  reserved: number;
  available: number;
  lowStockThreshold: number;
  status: 'ok' | 'low_stock' | 'out_of_stock';
  updatedAt: string;
}

export interface InventoryAdjustment {
  id: string;
  sku: string;
  productName: string;
  type: 'nhập' | 'điều chỉnh tăng' | 'điều chỉnh giảm' | 'hỏng' | 'kiểm kê';
  delta: number;
  beforeQty: number;
  afterQty: number;
  reason: string;
  note?: string;
  actor: string;
  referenceId: string;
  createdAt: string;
}

export type PreorderStatus = 'scheduled' | 'open' | 'closed' | 'awaiting_release' | 'arrived' | 'fulfilling' | 'completed' | 'cancelled';
export type DepositStatus = 'unpaid' | 'deposited' | 'deposit_failed' | 'fully_paid';

export interface PreorderBuyer {
  orderId: string;
  displayCode: string;
  customerName: string;
  customerPhone: string;
  quantity: number;
  depositStatus: DepositStatus;
  depositAmount: number;
  orderDate: string;
}

export interface PreorderBatch {
  id: string;
  batchCode: string;
  productId: string;
  productName: string;
  variantName?: string;
  image: string;
  series: string;
  manufacturer: string;
  startDate: string;
  closeDate: string;
  expectedReleaseDate: string;
  estimatedArrivalDate: string;
  totalSlots: number;
  reservedSlots: number;
  depositAmount: number;
  fullPrice: number;
  status: PreorderStatus;
  depositPolicy: string;
  supplierStatus: string;
  buyers: PreorderBuyer[];
  updatedAt: string;
}

export interface CMSSection {
  id: string;
  type: 'announcement' | 'hero' | 'categories' | 'trending' | 'collections' | 'preorder_spotlight' | 'shop_by_anime' | 'new_arrivals' | 'collectors_corner';
  title: string;
  enabled: boolean;
  position: number;
  settings: Record<string, any>;
  lastEditedBy: string;
  updatedAt: string;
}

export interface MediaAsset {
  id: string;
  fileName: string;
  url: string;
  folder: 'products' | 'hero' | 'categories' | 'branding';
  mimeType: string;
  sizeBytes: number;
  width: number;
  height: number;
  altText: string;
  usedIn: string[];
  createdAt: string;
}

export interface Customer {
  id: string;
  name: string;
  email: string;
  phone: string;
  avatar: string;
  tier: 'Standard' | 'Silver' | 'Gold' | 'VIP Wibu';
  totalSpent: number;
  ordersCount: number;
  lastOrderDate: string;
  status: 'active' | 'blocked';
  address: string;
  notes?: string;
}

export interface Promotion {
  id: string;
  code: string;
  name: string;
  type: 'percentage' | 'fixed_amount' | 'free_shipping';
  value: number;
  minOrderAmount: number;
  maxDiscount?: number;
  usageLimit: number;
  usageCount: number;
  startDate: string;
  endDate: string;
  status: 'active' | 'scheduled' | 'expired' | 'disabled';
}

export interface Review {
  id: string;
  productId: string;
  productName: string;
  productImage: string;
  customerName: string;
  rating: number;
  content: string;
  images?: string[];
  status: 'pending' | 'approved' | 'rejected' | 'reported';
  rejectionReason?: string;
  createdAt: string;
}

export interface TeamMember {
  id: string;
  name: string;
  email: string;
  role: 'owner' | 'store_manager' | 'catalog_editor' | 'fulfillment' | 'support' | 'analyst';
  roleName: string;
  status: 'active' | 'invited' | 'suspended';
  lastActive: string;
}

export interface AuditLog {
  id: string;
  actor: string;
  actorRole: string;
  action: string;
  resource: string;
  resourceId: string;
  changeSummary: string;
  correlationId: string;
  result: 'success' | 'failure';
  timestamp: string;
}

export interface TaskItem {
  id: string;
  title: string;
  type: 'order' | 'inventory' | 'preorder' | 'review' | 'system';
  priority: 'high' | 'medium' | 'low';
  link: string;
  status: 'pending' | 'in_progress' | 'done';
  dueNotice: string;
  createdAt: string;
}

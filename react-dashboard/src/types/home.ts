export interface BannerContent {
  titleSegments: Array<{
    text: string;
    highlight?: boolean;
  }>;
  subtitle: string;
  imageUrl?: string;
  trackingPlaceholder: string;
  trackingButtonLabel: string;
}

export interface ServiceItem {
  id: string;
  title: string;
  description: string;
  imageUrl: string;
  link: string;
}

export interface WhyCourierItem {
  id: string;
  title: string;
  imageUrl: string;
}

export interface PricingRate {
  id: string;
  weight: string;
  category: string;
  price: number;
}

export type PricingTierKey = 'same_day' | 'next_day' | 'sub_city' | 'outside_city';

export interface PricingTier {
  id: PricingTierKey;
  label: string;
  rates: PricingRate[];
}

export interface AchievementItem {
  id: string;
  icon: string;
  label: string;
  value: number;
  suffix?: string;
}

export interface PartnerItem {
  id: string;
  name: string;
  imageUrl: string;
  link: string;
}

export interface BlogItem {
  id: string;
  title: string;
  imageUrl: string;
  author: string;
  views: number;
  updatedAt: string;
  link: string;
}

export interface HomePageContent {
  banner: BannerContent;
  services: ServiceItem[];
  whyCourier: WhyCourierItem[];
  pricing: PricingTier[];
  achievements: AchievementItem[];
  partners: PartnerItem[];
  blogs: BlogItem[];
}

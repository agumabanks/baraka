import type { HomePageContent } from '../types/home';

const placeholderImage =
  'data:image/svg+xml;utf8,' +
  encodeURIComponent(
    `<svg xmlns="http://www.w3.org/2000/svg" width="480" height="320" viewBox="0 0 480 320">` +
      `<rect width="480" height="320" fill="#f5f5f5"/>` +
      `<text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#999999" font-family="Helvetica, Arial, sans-serif" font-size="24">Placeholder</text>` +
    `</svg>`
  );

export const mockHomeData: HomePageContent = {
  banner: {
    titleSegments: [
      { text: 'Logistics' },
      { text: 'Delivered Right', highlight: true },
      { text: 'On Time' },
    ],
    subtitle: 'Track parcels, manage deliveries, and delight customers with a frictionless courier experience.',
    imageUrl: placeholderImage,
    trackingPlaceholder: 'Enter tracking ID',
    trackingButtonLabel: 'Track Now',
  },
  services: [
    {
      id: 'express',
      title: 'Express Delivery',
      description: 'Same-day pickup and delivery service tailored for urgent parcels with guaranteed time slots.',
      imageUrl: placeholderImage,
      link: '/services/express',
    },
    {
      id: 'next-day',
      title: 'Next Day Delivery',
      description: 'Cost-effective delivery that keeps reliability high with early morning distribution runs.',
      imageUrl: placeholderImage,
      link: '/services/next-day',
    },
    {
      id: 'sub-city',
      title: 'Sub City',
      description: 'Optimized micro-fulfillment hubs keep suburban deliveries efficient and predictable.',
      imageUrl: placeholderImage,
      link: '/services/sub-city',
    },
    {
      id: 'outside-city',
      title: 'Outside City',
      description: 'Cross-city deliveries with dedicated linehaul support and transparent milestone tracking.',
      imageUrl: placeholderImage,
      link: '/services/outside-city',
    },
  ],
  whyCourier: [
    {
      id: 'coverage',
      title: 'Nationwide Coverage',
      imageUrl: placeholderImage,
    },
    {
      id: 'visibility',
      title: 'Real-time Visibility',
      imageUrl: placeholderImage,
    },
    {
      id: 'support',
      title: 'Dedicated Support',
      imageUrl: placeholderImage,
    },
  ],
  pricing: [
    {
      id: 'same_day',
      label: 'Same Day',
      rates: [
        { id: 'sd-1', weight: 'Up to 1kg', category: 'Documents', price: 6.5 },
        { id: 'sd-2', weight: 'Up to 3kg', category: 'Parcel', price: 8.75 },
        { id: 'sd-3', weight: 'Up to 5kg', category: 'Parcel', price: 11.0 },
      ],
    },
    {
      id: 'next_day',
      label: 'Next Day',
      rates: [
        { id: 'nd-1', weight: 'Up to 1kg', category: 'Documents', price: 4.5 },
        { id: 'nd-2', weight: 'Up to 3kg', category: 'Parcel', price: 6.25 },
        { id: 'nd-3', weight: 'Up to 5kg', category: 'Parcel', price: 7.75 },
      ],
    },
    {
      id: 'sub_city',
      label: 'Sub City',
      rates: [
        { id: 'sc-1', weight: 'Up to 1kg', category: 'Documents', price: 5.25 },
        { id: 'sc-2', weight: 'Up to 3kg', category: 'Parcel', price: 7.25 },
        { id: 'sc-3', weight: 'Up to 5kg', category: 'Parcel', price: 9.0 },
      ],
    },
    {
      id: 'outside_city',
      label: 'Outside City',
      rates: [
        { id: 'oc-1', weight: 'Up to 1kg', category: 'Documents', price: 9.0 },
        { id: 'oc-2', weight: 'Up to 3kg', category: 'Parcel', price: 12.5 },
        { id: 'oc-3', weight: 'Up to 5kg', category: 'Parcel', price: 16.0 },
      ],
    },
  ],
  achievements: [
    {
      id: 'branches',
      icon: 'fas fa-map-marker-alt',
      label: 'Active Branches',
      value: 48,
    },
    {
      id: 'parcels',
      icon: 'fas fa-box',
      label: 'Parcels Delivered',
      value: 125000,
    },
    {
      id: 'merchants',
      icon: 'fas fa-store',
      label: 'Merchants Onboarded',
      value: 980,
    },
    {
      id: 'reviews',
      icon: 'fas fa-star',
      label: 'Five-star Reviews',
      value: 4200,
      suffix: '+',
    },
  ],
  partners: [
    {
      id: 'partner-1',
      name: 'Northwind',
      imageUrl: placeholderImage,
      link: 'https://example.com/partners/northwind',
    },
    {
      id: 'partner-2',
      name: 'Contoso',
      imageUrl: placeholderImage,
      link: 'https://example.com/partners/contoso',
    },
    {
      id: 'partner-3',
      name: 'Globex',
      imageUrl: placeholderImage,
      link: 'https://example.com/partners/globex',
    },
    {
      id: 'partner-4',
      name: 'Initech',
      imageUrl: placeholderImage,
      link: 'https://example.com/partners/initech',
    },
  ],
  blogs: [
    {
      id: 'blog-1',
      title: 'Delivering Delight with a Lean Courier Stack',
      imageUrl: placeholderImage,
      author: 'Alex Johnson',
      views: 1820,
      updatedAt: '2024-11-02',
      link: '/blog/delivering-delight',
    },
    {
      id: 'blog-2',
      title: 'How to Design a Customer-first Returns Journey',
      imageUrl: placeholderImage,
      author: 'Samantha Lee',
      views: 1345,
      updatedAt: '2024-10-18',
      link: '/blog/customer-first-returns',
    },
    {
      id: 'blog-3',
      title: 'Scaling Logistics with Predictive Analytics',
      imageUrl: placeholderImage,
      author: 'David Kim',
      views: 2015,
      updatedAt: '2024-09-25',
      link: '/blog/predictive-analytics',
    },
  ],
};

import type { FooterProps } from '../types/footer'

export const mockFooterData: FooterProps = {
  copyright: 'All rights reserved.',
  companyName: 'Dashboard',
  version: '1.0.0',
  links: [
    {
      label: 'Privacy Policy',
      href: '/privacy'
    },
    {
      label: 'Terms of Service',
      href: '/terms'
    },
    {
      label: 'Support',
      href: '/support'
    }
  ]
}
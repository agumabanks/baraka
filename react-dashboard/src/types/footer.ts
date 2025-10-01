export interface FooterLink {
  label: string
  href: string
  external?: boolean
}

export interface FooterProps {
  copyright: string
  companyName?: string
  version?: string
  links?: FooterLink[]
  className?: string
}
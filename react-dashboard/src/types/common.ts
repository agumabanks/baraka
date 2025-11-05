export interface PaginationMeta {
  current_page: number;
  per_page: number;
  last_page: number;
  total: number;
}

export interface PaginationLinks {
  first: string | null;
  last: string | null;
  prev: string | null;
  next: string | null;
}

export interface PaginatedResponse<T> {
  data: T[];
  meta?: PaginationMeta;
  links?: PaginationLinks;
  success?: boolean;
  message?: string;
}

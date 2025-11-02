export type ShortUrl = {
    code: string;
    short_url: string;
    url: string;
    clicks: number;
    created_at: string;
    last_accessed_at?: string | null;
};

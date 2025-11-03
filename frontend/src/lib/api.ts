import type {ShortUrl} from '../types';

const baseUrl = import.meta.env.VITE_API_BASE_URL || '/api';

async function handle<T>(res: Response): Promise<T> {
    if (!res.ok) {
        let msg = 'Request failed';

        try {
            const data = await res.json();
            msg = data.error || JSON.stringify(data);
        } catch {}

        throw new Error(msg);
    }

    return (await res.json()) as Promise<T>;
}

export async function shortenUrl(url: string): Promise<ShortUrl> {
    const res = await fetch(`${baseUrl}/urls`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({url}),
    });

    return handle<ShortUrl>(res);
}

export async function listUrls(limit = 50, offset = 0): Promise<ShortUrl[]> {
    const res = await fetch(`${baseUrl}/urls?limit=${limit}&offset=${offset}`, {
        method: 'GET',
    });

    return handle<ShortUrl[]>(res);
}

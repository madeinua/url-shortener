import React, {useState} from 'react';
import type {ShortUrl} from '../types';

type ShortenFormProps = {
    onCreated: (item: ShortUrl, existed: boolean) => void;
    notify: (msg: string) => void;
};

export default function ShortenForm({onCreated, notify}: ShortenFormProps) {
    const [url, setUrl] = useState('');
    const [loading, setLoading] = useState(false);
    const baseUrl = import.meta.env.VITE_API_BASE_URL || '/api';
    const isDisabledSubmit = loading || url.trim().length === 0;
    const isDisabledClear = loading || url.trim().length === 0;

    async function submit(e: React.FormEvent) {
        e.preventDefault();

        if (!url.trim()) {
            return;
        }

        try {
            setLoading(true);

            const res = await fetch(baseUrl + '/urls', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({url}),
            });

            const existed = res.status === 200;
            const data: ShortUrl = await res.json();

            onCreated(data, existed);

            if (existed) {
                notify('Already shortened - showing existing link.');
            } else {
                notify('Short URL created.');
            }

            setUrl('');
        } catch (err: any) {
            notify(err.message || 'Failed to shorten URL');
        } finally {
            setLoading(false);
        }
    }

    return (
        <form onSubmit={submit} className="card space-y-3">
            <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                    Enter a URL to shorten
                </label>
                <input
                    className="input"
                    placeholder="https://example.com/page?a=1&b=2"
                    value={url}
                    onChange={(e) => setUrl(e.target.value)}
                />
            </div>
            <div className="flex gap-3">
                <button
                    type="submit"
                    disabled={isDisabledSubmit}
                    aria-busy={loading}
                    className="btn-primary disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {loading ? 'Working...' : 'Shorten'}
                </button>
                <button
                    type="button"
                    className="btn-outline disabled:opacity-50 disabled:cursor-not-allowed"
                    onClick={() => setUrl('')}
                    disabled={isDisabledClear}
                >
                    Clear
                </button>
            </div>
        </form>
    );
}

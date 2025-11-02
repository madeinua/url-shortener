import {useEffect, useState} from 'react';
import ShortenForm from './components/ShortenForm';
import UrlTable from './components/UrlTable';
import {listUrls} from './lib/api';
import type {ShortUrl} from './types';

type NotificationType = {id: number; text: string};
let nextId = 1;

export default function App() {
    const [items, setItems] = useState<ShortUrl[]>([]);
    const [notifications, setNotifications] = useState<NotificationType[]>([]);
    const [loading, setLoading] = useState(false);
    const [lastItem, setLastItem] = useState<{item: ShortUrl; existed: boolean} | null>(null);

    function notify(text: string) {
        const t = {id: nextId++, text};
        setNotifications((prev) => [...prev, t]);
        setTimeout(() => setNotifications((prev) => prev.filter((x) => x.id !== t.id)), 2500);
    }

    function uniqByCode(list: ShortUrl[]): ShortUrl[] {
        const seen = new Set<string>();
        const out: ShortUrl[] = [];

        for (const it of list) {
            if (it && !seen.has(it.code)) {
                seen.add(it.code);
                out.push(it);
            }
        }

        return out;
    }

    async function refresh() {
        setLoading(true);

        try {
            const data = await listUrls(50, 0);
            setItems((prev) => {
                return uniqByCode([...data, ...prev]);
            });
        } catch (e: any) {
            notify(e.message || 'Failed to load list');
        } finally {
            setLoading(false);
        }
    }

    function handleCreated(it: ShortUrl, existed: boolean) {
        setLastItem({item: it, existed});
        setItems((prev) => uniqByCode([it, ...prev]));
    }

    function handleOpen(_code: string) {
        setTimeout(() => refresh(), 400);
    }

    function copyToClipboard(text: string) {
        navigator.clipboard
            .writeText(text)
            .then(() => notify('Copied to clipboard'))
            .catch(() => notify('Copy failed'));
    }

    useEffect(() => {
        refresh();
    }, []);

    return (
        <div>
            <header className="max-w-6xl mx-auto px-4 pt-10 pb-6">
                <h1 className="text-2xl font-semibold">URL Shortener</h1>
                <p className="muted mt-1">
                    Create short links and track click counts. Backend: Symfony.
                </p>
            </header>
            <main className="max-w-6xl mx-auto px-4 space-y-6 pb-16">
                <ShortenForm onCreated={handleCreated} notify={notify} />

                {lastItem && (
                    <div className="card flex items-center justify-between gap-4 flex-wrap">
                        <div>
                            <div className="text-sm text-gray-600">
                                {lastItem.existed ? 'Existing short link' : 'New short link'}
                            </div>
                            <a
                                href={lastItem.item.short_url}
                                target="_blank"
                                rel="noreferrer"
                                onClick={() => handleOpen(lastItem.item.code)}
                                className="text-blue-600 underline break-words"
                            >
                                {lastItem.item.short_url}
                            </a>
                            <div className="text-sm text-gray-600 mt-1 break-words">
                                → {lastItem.item.url}
                            </div>
                        </div>
                        <div className="flex gap-2">
                            <button
                                className="btn-outline"
                                onClick={() => copyToClipboard(lastItem.item.short_url)}
                            >
                                Copy
                            </button>
                            <a
                                className="btn-primary"
                                href={lastItem.item.short_url}
                                target="_blank"
                                rel="noreferrer"
                                onClick={() => handleOpen(lastItem.item.code)}
                            >
                                Open
                            </a>
                        </div>
                    </div>
                )}

                {loading ? (
                    <div className="card">Loading...</div>
                ) : (
                    <UrlTable items={items} onOpen={handleOpen} notify={notify} />
                )}
            </main>

            <div className="fixed bottom-4 right-4 space-y-2">
                {notifications.map((t) => (
                    <div key={t.id} className="card shadow-lg">
                        {t.text}
                    </div>
                ))}
            </div>
        </div>
    );
}

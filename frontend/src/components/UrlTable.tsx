import type {ShortUrl} from '../types';

type Props = {
    items: ShortUrl[];
    onOpen: (code: string) => void;
    notify: (msg: string) => void;
};

function copy(text: string, notify: (m: string) => void) {
    navigator.clipboard
        .writeText(text)
        .then(() => notify('Copied to clipboard'))
        .catch(() => notify('Copy failed'));
}

export default function UrlTable({items, onOpen, notify}: Props) {
    if (!items.length) {
        return <div className="card muted">No URLs yet - shorten one above.</div>;
    }

    return (
        <div className="card overflow-x-auto">
            <table className="table">
                <thead>
                    <tr>
                        <th className="th">Short URL</th>
                        <th className="th">Original URL</th>
                        <th className="th">Clicks</th>
                        <th className="th">Created</th>
                        <th className="th"></th>
                    </tr>
                </thead>
                <tbody>
                    {items.map((it) => (
                        <tr key={it.code} className="border-t">
                            <td className="td">
                                <a
                                    href={it.short_url}
                                    target="_blank"
                                    rel="noreferrer"
                                    onClick={() => onOpen(it.code)}
                                    className="text-blue-600 underline"
                                >
                                    {it.short_url}
                                </a>
                            </td>
                            <td className="td max-w-[32rem]">
                                <a
                                    href={it.url}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="text-gray-800 underline break-words"
                                >
                                    {it.url}
                                </a>
                            </td>
                            <td className="td">{it.clicks}</td>
                            <td className="td">
                                <time dateTime={it.created_at}>
                                    {new Date(it.created_at).toLocaleString()}
                                </time>
                            </td>
                            <td className="td">
                                <button
                                    className="btn-outline"
                                    onClick={() => copy(it.short_url, notify)}
                                >
                                    Copy
                                </button>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

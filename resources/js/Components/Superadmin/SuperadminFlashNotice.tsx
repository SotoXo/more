export default function SuperadminFlashNotice({
    success,
    error,
}: {
    success?: string | null;
    error?: string | null;
}) {
    if (!success && !error) {
        return null;
    }

    const isError = Boolean(error);
    const message = error ?? success;

    return (
        <div
            className={[
                'rounded-[1.4rem] border px-4 py-3 text-sm',
                isError
                    ? 'border-[rgba(183,108,112,0.28)] bg-[rgba(183,108,112,0.12)] text-[rgb(245,208,210)]'
                    : 'border-[rgba(113,168,148,0.3)] bg-[rgba(113,168,148,0.12)] text-[rgb(205,241,226)]',
            ].join(' ')}
        >
            {message}
        </div>
    );
}

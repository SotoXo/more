export default function SuperadminLoadingState() {
    return (
        <div className="sa-panel rounded-[2rem] p-6">
            <div className="animate-pulse space-y-4">
                <div className="h-5 w-40 rounded-full bg-white/10" />
                <div className="grid gap-3">
                    {Array.from({ length: 6 }).map((_, index) => (
                        <div
                            key={index}
                            className="grid gap-3 rounded-[1.3rem] border border-white/6 bg-white/[0.03] p-4 md:grid-cols-[1.3fr_1fr_1fr_0.8fr]"
                        >
                            <div className="space-y-2">
                                <div className="h-4 w-48 rounded-full bg-white/10" />
                                <div className="h-3 w-32 rounded-full bg-white/6" />
                            </div>
                            <div className="h-4 w-full rounded-full bg-white/8" />
                            <div className="h-4 w-full rounded-full bg-white/8" />
                            <div className="h-4 w-24 rounded-full bg-white/8" />
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}

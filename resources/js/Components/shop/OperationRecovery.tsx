type Props = { error?: string; onRenew: () => void };

export default function OperationRecovery({ error, onRenew }: Props) {
    if (!error) return null;
    return (
        <div className="my-4 rounded-xl border border-amber-300/30 bg-amber-950/30 p-4 text-sm text-amber-100" role="alert">
            <p>{error}</p>
            <p className="mt-2">Antes de volver a registrar, revisa tus ventas o reservas: la operación anterior podría haberse completado.</p>
            <button type="button" onClick={onRenew} className="mt-3 rounded-full border border-amber-200/50 px-4 py-2 font-semibold hover:bg-amber-200/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-200">
                Iniciar nueva operación
            </button>
        </div>
    );
}

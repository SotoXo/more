import { BarChart3 } from 'lucide-react';

export default function EmptyChartState({
    message = 'Aún no hay datos suficientes para esta gráfica.',
    compact = false,
}: {
    message?: string;
    compact?: boolean;
}) {
    return (
        <div className={compact ? 'sa-empty-chart sa-empty-chart--compact' : 'sa-empty-chart'}>
            <span className="sa-empty-chart__icon">
                <BarChart3 size={20} />
            </span>
            <p>{message}</p>
        </div>
    );
}

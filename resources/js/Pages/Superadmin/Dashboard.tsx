import AdminAlertGrid from '@/Components/Superadmin/Dashboard/AdminAlertGrid';
import DashboardChartCard from '@/Components/Superadmin/Dashboard/DashboardChartCard';
import DashboardStatCard from '@/Components/Superadmin/Dashboard/DashboardStatCard';
import DonutSummary from '@/Components/Superadmin/Dashboard/DonutSummary';
import HorizontalBarList from '@/Components/Superadmin/Dashboard/HorizontalBarList';
import MiniAreaChart from '@/Components/Superadmin/Dashboard/MiniAreaChart';
import SummaryList from '@/Components/Superadmin/Dashboard/SummaryList';
import SuperadminLayout from '@/Components/Superadmin/SuperadminLayout';
import SuperadminPageHeader from '@/Components/Superadmin/SuperadminPageHeader';
import type { SuperadminDashboardPayload } from '@/types/superadmin';

type DashboardProps = {
    dashboard: SuperadminDashboardPayload;
};

export default function Dashboard({ dashboard }: DashboardProps) {
    return (
        <SuperadminLayout title="Mati Boutique | Resumen administrativo">
            <div className="space-y-8">
                <SuperadminPageHeader
                    eyebrow="Resumen"
                    title="Resumen administrativo"
                    description="Indicadores clave de catálogo, inventario, ventas, reservas y actividad reciente."
                    breadcrumbs={[{ label: 'Panel administrativo' }]}
                />

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    {dashboard.metrics.map((metric) => (
                        <DashboardStatCard key={metric.key} metric={metric} />
                    ))}
                </section>

                <section className="grid gap-4 xl:grid-cols-3">
                    <DashboardChartCard
                        title="Stock por estado"
                        description="Lectura rápida de disponibilidad y reservas."
                    >
                        <DonutSummary
                            data={dashboard.charts.stockByState}
                            emptyMessage="Aún no hay datos suficientes de inventario."
                        />
                    </DashboardChartCard>

                    <DashboardChartCard
                        title="Ventas últimos 30 días"
                        description="Tendencia real de ventas registradas."
                    >
                        <MiniAreaChart
                            data={dashboard.charts.salesByPeriod}
                            emptyMessage="Aún no hay ventas registradas para mostrar esta gráfica."
                        />
                    </DashboardChartCard>

                    <DashboardChartCard
                        title="Reservas por estado"
                        description="Distribución administrativa de reservas."
                    >
                        <DonutSummary
                            data={dashboard.charts.reservationsByState}
                            emptyMessage="No hay reservas registradas."
                        />
                    </DashboardChartCard>
                </section>

                <section className="grid gap-4 xl:grid-cols-3">
                    <DashboardChartCard
                        title="Stock por categoría"
                        description="Ranking de unidades disponibles."
                    >
                        <HorizontalBarList
                            data={dashboard.charts.stockByCategory}
                            emptyMessage="Aún no hay inventario disponible para graficar."
                        />
                    </DashboardChartCard>

                    <DashboardChartCard
                        title="Prendas por categoría"
                        description="Categorías con más prendas registradas."
                    >
                        <HorizontalBarList
                            data={dashboard.charts.productsByCategory}
                            emptyMessage="Aún no hay prendas registradas para graficar."
                        />
                    </DashboardChartCard>

                    <DashboardChartCard
                        title="Top marcas"
                        description="Marcas con mayor presencia en catálogo."
                    >
                        <HorizontalBarList
                            data={dashboard.charts.topBrands}
                            emptyMessage="Aún no hay marcas con prendas."
                        />
                    </DashboardChartCard>
                </section>

                <section className="grid gap-4 xl:grid-cols-2">
                    <DashboardChartCard
                        title="Top categorías"
                        description="Ranking ejecutivo por volumen de prendas."
                    >
                        <HorizontalBarList
                            data={dashboard.charts.topCategories}
                            emptyMessage="Aún no hay categorías con prendas."
                        />
                    </DashboardChartCard>

                    <DashboardChartCard
                        title="Favoritos por prenda"
                        description="Prendas más guardadas por clientes."
                    >
                        <HorizontalBarList
                            data={dashboard.charts.topFavorites}
                            emptyMessage="Aún no hay favoritos registrados."
                        />
                    </DashboardChartCard>
                </section>

                <section className="grid gap-4 xl:grid-cols-2">
                    <DashboardChartCard title="Productos con bajo stock">
                        <SummaryList
                            items={dashboard.lists.lowStock}
                            emptyMessage="No hay productos con bajo stock."
                        />
                    </DashboardChartCard>

                    <DashboardChartCard title="Últimas prendas registradas">
                        <SummaryList
                            items={dashboard.lists.recentProducts}
                            emptyMessage="Aún no hay prendas registradas."
                        />
                    </DashboardChartCard>
                </section>

                <section className="grid gap-4 xl:grid-cols-2">
                    <DashboardChartCard title="Últimas ventas">
                        <SummaryList
                            items={dashboard.lists.recentSales}
                            emptyMessage="Aún no hay ventas registradas para mostrar estadísticas."
                        />
                    </DashboardChartCard>

                    <DashboardChartCard title="Últimas reservas">
                        <SummaryList
                            items={dashboard.lists.recentReservations}
                            emptyMessage="Aún no hay reservas registradas."
                        />
                    </DashboardChartCard>
                </section>

                <DashboardChartCard
                    title="Alertas administrativas"
                    description="Puntos de control para mantener el catálogo y la operación ordenados."
                >
                    <AdminAlertGrid alerts={dashboard.lists.alerts} />
                </DashboardChartCard>
            </div>
        </SuperadminLayout>
    );
}

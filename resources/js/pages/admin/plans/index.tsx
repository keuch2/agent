import { Head, Link } from '@inertiajs/react';
import { Check } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { edit, index } from '@/routes/admin/plans';

export type Plan = {
    id: number;
    name: string;
    monthly_price_usd: string;
    conversation_quota: number;
    advisor_limit: number | null;
    overage_price_usd: string;
    features: string[];
    is_active: boolean;
    tenants_count?: number;
};

export const usd = (value: string | number, digits = 2) =>
    `USD ${Number(value).toLocaleString('es-PY', { minimumFractionDigits: digits, maximumFractionDigits: digits })}`;

export default function PlansIndex({
    plans,
    features,
}: {
    plans: Plan[];
    features: Record<string, string>;
}) {
    return (
        <>
            <Head title="Planes" />
            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Planes"
                    description="Precios en USD con IVA incluido. Los cambios aplican a todas las empresas del plan."
                />
                <div className="grid gap-4 md:grid-cols-3">
                    {plans.map((plan) => (
                        <Card
                            key={plan.id}
                            className={plan.is_active ? '' : 'opacity-60'}
                        >
                            <CardHeader>
                                <div className="flex items-center justify-between gap-2">
                                    <CardTitle>{plan.name}</CardTitle>
                                    {!plan.is_active && (
                                        <Badge variant="outline">
                                            Inactivo
                                        </Badge>
                                    )}
                                </div>
                                <p className="text-2xl font-semibold tabular-nums">
                                    {usd(plan.monthly_price_usd, 0)}
                                    <span className="text-sm font-normal text-muted-foreground">
                                        /mes
                                    </span>
                                </p>
                            </CardHeader>
                            <CardContent className="space-y-2 text-sm">
                                <p>
                                    {plan.conversation_quota.toLocaleString(
                                        'es-PY',
                                    )}{' '}
                                    conversaciones/mes
                                </p>
                                <p>
                                    {plan.advisor_limit === null
                                        ? 'Asesores sin límite'
                                        : `Hasta ${plan.advisor_limit} asesores`}
                                </p>
                                <p className="text-muted-foreground">
                                    Excedente: {usd(plan.overage_price_usd)} por
                                    conversación
                                </p>
                                {plan.features.map((feature) => (
                                    <p key={feature} className="flex gap-2">
                                        <Check className="mt-0.5 size-4 shrink-0" />{' '}
                                        {features[feature] ?? feature}
                                    </p>
                                ))}
                            </CardContent>
                            <CardFooter className="justify-between text-sm text-muted-foreground">
                                <span>{plan.tenants_count ?? 0} empresas</span>
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={edit(plan.id)}>Editar</Link>
                                </Button>
                            </CardFooter>
                        </Card>
                    ))}
                </div>
            </div>
        </>
    );
}

PlansIndex.layout = {
    breadcrumbs: [{ title: 'Planes', href: index() }],
};

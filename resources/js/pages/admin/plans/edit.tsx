import { Form, Head } from '@inertiajs/react';
import PlanController from '@/actions/App/Http/Controllers/Admin/PlanController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Plan } from '@/pages/admin/plans/index';
import { index } from '@/routes/admin/plans';

export default function PlanEdit({
    plan,
    features,
}: {
    plan: Plan;
    features: Record<string, string>;
}) {
    return (
        <>
            <Head title={`Plan ${plan.name}`} />
            <div className="max-w-2xl p-4">
                <Heading
                    title={`Plan ${plan.name}`}
                    description="Los valores se aplican desde el próximo cálculo de uso"
                />

                <Form
                    {...PlanController.update.form(plan.id)}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-6 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Nombre</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        defaultValue={plan.name}
                                        required
                                    />
                                    <InputError message={errors.name} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="monthly_price_usd">
                                        Precio mensual (USD, IVA incl.)
                                    </Label>
                                    <Input
                                        id="monthly_price_usd"
                                        name="monthly_price_usd"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        defaultValue={plan.monthly_price_usd}
                                        required
                                    />
                                    <InputError
                                        message={errors.monthly_price_usd}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="conversation_quota">
                                        Conversaciones por mes
                                    </Label>
                                    <Input
                                        id="conversation_quota"
                                        name="conversation_quota"
                                        type="number"
                                        min="0"
                                        defaultValue={plan.conversation_quota}
                                        required
                                    />
                                    <InputError
                                        message={errors.conversation_quota}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="advisor_limit">
                                        Límite de asesores
                                    </Label>
                                    <Input
                                        id="advisor_limit"
                                        name="advisor_limit"
                                        type="number"
                                        min="1"
                                        defaultValue={plan.advisor_limit ?? ''}
                                        placeholder="Sin límite"
                                    />
                                    <p className="text-xs text-muted-foreground">
                                        Vacío = sin límite
                                    </p>
                                    <InputError
                                        message={errors.advisor_limit}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="overage_price_usd">
                                        Excedente por conversación (USD)
                                    </Label>
                                    <Input
                                        id="overage_price_usd"
                                        name="overage_price_usd"
                                        type="number"
                                        step="0.0001"
                                        min="0"
                                        defaultValue={plan.overage_price_usd}
                                        required
                                    />
                                    <InputError
                                        message={errors.overage_price_usd}
                                    />
                                </div>
                            </div>

                            <fieldset className="space-y-3">
                                <legend className="mb-2 text-sm font-medium">
                                    Funciones incluidas
                                </legend>
                                {Object.entries(features).map(
                                    ([key, label]) => (
                                        <label
                                            key={key}
                                            className="flex items-start gap-3 text-sm"
                                        >
                                            <input
                                                type="checkbox"
                                                name="features[]"
                                                value={key}
                                                defaultChecked={plan.features.includes(
                                                    key,
                                                )}
                                                className="mt-0.5 size-4 accent-primary"
                                            />
                                            {label}
                                        </label>
                                    ),
                                )}
                                <InputError message={errors.features} />
                            </fieldset>

                            <label className="flex items-center gap-3 text-sm">
                                <input
                                    type="hidden"
                                    name="is_active"
                                    value="0"
                                />
                                <input
                                    type="checkbox"
                                    name="is_active"
                                    value="1"
                                    defaultChecked={plan.is_active}
                                    className="size-4 accent-primary"
                                />
                                Disponible para nuevas empresas
                            </label>

                            <Button disabled={processing}>Guardar plan</Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

PlanEdit.layout = {
    breadcrumbs: [{ title: 'Planes', href: index() }],
};

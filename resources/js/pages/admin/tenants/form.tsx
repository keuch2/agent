import { Form, Head } from '@inertiajs/react';
import TenantController from '@/actions/App/Http/Controllers/Admin/TenantController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { NativeSelect } from '@/components/native-select';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { index } from '@/routes/admin/tenants';

type Tenant = {
    id: number;
    name: string;
    legal_name: string | null;
    ruc: string | null;
    timezone: string;
    plan_id: number;
    status: string;
};

type Props = {
    tenant?: Tenant;
    plans: { id: number; name: string; monthly_price_usd: string }[];
    statuses: { value: string; label: string }[];
    timezones: string[];
};

function Field({
    id,
    label,
    error,
    hint,
    children,
}: {
    id: string;
    label: string;
    error?: string;
    hint?: string;
    children: React.ReactNode;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>{label}</Label>
            {children}
            {hint && <p className="text-xs text-muted-foreground">{hint}</p>}
            <InputError message={error} />
        </div>
    );
}

export default function TenantForm({
    tenant,
    plans,
    statuses,
    timezones,
}: Props) {
    const editing = tenant !== undefined;
    const action = editing
        ? TenantController.update.form(tenant.id)
        : TenantController.store.form();

    return (
        <>
            <Head title={editing ? tenant.name : 'Nueva empresa'} />
            <div className="max-w-2xl p-4">
                <Heading
                    title={editing ? tenant.name : 'Nueva empresa'}
                    description={
                        editing
                            ? 'Datos de la empresa, plan y estado'
                            : 'Al guardar, el administrador recibe un correo para definir su contraseña'
                    }
                />

                <Form
                    {...action}
                    options={{ preserveScroll: true }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-6 sm:grid-cols-2">
                                <Field
                                    id="name"
                                    label="Nombre comercial"
                                    error={errors.name}
                                >
                                    <Input
                                        id="name"
                                        name="name"
                                        defaultValue={tenant?.name}
                                        required
                                    />
                                </Field>
                                <Field
                                    id="legal_name"
                                    label="Razón social"
                                    error={errors.legal_name}
                                >
                                    <Input
                                        id="legal_name"
                                        name="legal_name"
                                        defaultValue={tenant?.legal_name ?? ''}
                                    />
                                </Field>
                                <Field id="ruc" label="RUC" error={errors.ruc}>
                                    <Input
                                        id="ruc"
                                        name="ruc"
                                        defaultValue={tenant?.ruc ?? ''}
                                        placeholder="80012345-6"
                                        inputMode="numeric"
                                    />
                                </Field>
                                <Field
                                    id="timezone"
                                    label="Zona horaria"
                                    error={errors.timezone}
                                >
                                    <NativeSelect
                                        id="timezone"
                                        name="timezone"
                                        defaultValue={
                                            tenant?.timezone ??
                                            'America/Asuncion'
                                        }
                                        options={timezones.map((tz) => ({
                                            value: tz,
                                            label: tz,
                                        }))}
                                    />
                                </Field>
                                <Field
                                    id="plan_id"
                                    label="Plan"
                                    error={errors.plan_id}
                                >
                                    <NativeSelect
                                        id="plan_id"
                                        name="plan_id"
                                        defaultValue={tenant?.plan_id ?? ''}
                                        placeholder="Elegí un plan"
                                        required
                                        options={plans.map((plan) => ({
                                            value: plan.id,
                                            label: `${plan.name} · USD ${Number(plan.monthly_price_usd).toLocaleString('es-PY')}/mes`,
                                        }))}
                                    />
                                </Field>
                                {editing && (
                                    <Field
                                        id="status"
                                        label="Estado"
                                        error={errors.status}
                                    >
                                        <NativeSelect
                                            id="status"
                                            name="status"
                                            defaultValue={tenant.status}
                                            options={statuses}
                                        />
                                    </Field>
                                )}
                            </div>

                            {!editing && (
                                <>
                                    <Separator />
                                    <Heading
                                        variant="small"
                                        title="Administrador de la empresa"
                                        description="Primer usuario del panel; después puede dar de alta al resto del equipo"
                                    />
                                    <div className="grid gap-6 sm:grid-cols-2">
                                        <Field
                                            id="admin_name"
                                            label="Nombre"
                                            error={errors.admin_name}
                                        >
                                            <Input
                                                id="admin_name"
                                                name="admin_name"
                                                required
                                                autoComplete="off"
                                            />
                                        </Field>
                                        <Field
                                            id="admin_email"
                                            label="Correo"
                                            error={errors.admin_email}
                                        >
                                            <Input
                                                id="admin_email"
                                                name="admin_email"
                                                type="email"
                                                required
                                                autoComplete="off"
                                            />
                                        </Field>
                                    </div>
                                </>
                            )}

                            <Button disabled={processing}>
                                {editing ? 'Guardar cambios' : 'Crear empresa'}
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

TenantForm.layout = {
    breadcrumbs: [{ title: 'Empresas', href: index() }],
};

import { Form, Head, router } from '@inertiajs/react';
import { Pencil, Plus, ShieldCheck, Trash2 } from 'lucide-react';
import { useState } from 'react';
import UserController from '@/actions/App/Http/Controllers/Tenant/UserController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { NativeSelect } from '@/components/native-select';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/users';

type TenantUser = {
    id: number;
    name: string;
    email: string;
    role: string | null;
    role_label: string | null;
    two_factor: boolean;
    is_current: boolean;
};

type Option = { value: string; label: string };

function UserDialog({
    user,
    roles,
    open,
    onClose,
}: {
    user: TenantUser | null;
    roles: Option[];
    open: boolean;
    onClose: () => void;
}) {
    const action = user
        ? UserController.update.form(user.id)
        : UserController.store.form();

    return (
        <Dialog open={open} onOpenChange={(value) => !value && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {user ? 'Editar usuario' : 'Nuevo usuario'}
                    </DialogTitle>
                    <DialogDescription>
                        {user
                            ? 'Cambiá los datos o el rol del usuario.'
                            : 'Le enviamos un correo para que defina su contraseña.'}
                    </DialogDescription>
                </DialogHeader>
                <Form
                    {...action}
                    key={user?.id ?? 'new'}
                    options={{ preserveScroll: true }}
                    onSuccess={onClose}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nombre</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={user?.name}
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="email">Correo</Label>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    defaultValue={user?.email}
                                    required
                                />
                                <InputError message={errors.email} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="role">Rol</Label>
                                <NativeSelect
                                    id="role"
                                    name="role"
                                    defaultValue={user?.role ?? 'advisor'}
                                    options={roles}
                                    disabled={user?.is_current}
                                />
                                {user?.is_current && (
                                    <input
                                        type="hidden"
                                        name="role"
                                        value={user.role ?? ''}
                                    />
                                )}
                                <InputError message={errors.role} />
                            </div>
                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={onClose}
                                >
                                    Cancelar
                                </Button>
                                <Button disabled={processing}>
                                    {user ? 'Guardar' : 'Crear usuario'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export default function UsersIndex({
    users,
    roles,
}: {
    users: TenantUser[];
    roles: Option[];
}) {
    const [editing, setEditing] = useState<TenantUser | null>(null);
    const [open, setOpen] = useState(false);

    const openDialog = (user: TenantUser | null) => {
        setEditing(user);
        setOpen(true);
    };

    const remove = (user: TenantUser) => {
        if (
            confirm(`¿Eliminar a ${user.name}? Va a perder el acceso al panel.`)
        ) {
            router.delete(UserController.destroy.url(user.id), {
                preserveScroll: true,
            });
        }
    };

    return (
        <>
            <Head title="Usuarios" />
            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Usuarios"
                        description="Personas de tu equipo con acceso al panel"
                    />
                    <Button onClick={() => openDialog(null)}>
                        <Plus /> Nuevo usuario
                    </Button>
                </div>

                <ul className="divide-y rounded-xl border">
                    {users.map((user) => (
                        <li
                            key={user.id}
                            className="flex flex-wrap items-center gap-3 px-4 py-3"
                        >
                            <div className="min-w-0 flex-1">
                                <p className="truncate font-medium">
                                    {user.name}
                                    {user.is_current && (
                                        <span className="ml-2 text-xs text-muted-foreground">
                                            (vos)
                                        </span>
                                    )}
                                </p>
                                <p className="truncate text-sm text-muted-foreground">
                                    {user.email}
                                </p>
                            </div>
                            {user.two_factor && (
                                <ShieldCheck
                                    className="size-4 text-muted-foreground"
                                    aria-label="Verificación en dos pasos activa"
                                />
                            )}
                            <Badge variant="secondary">
                                {user.role_label ?? 'Sin rol'}
                            </Badge>
                            <div className="flex gap-1">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    onClick={() => openDialog(user)}
                                    aria-label={`Editar a ${user.name}`}
                                >
                                    <Pencil />
                                </Button>
                                {!user.is_current && (
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => remove(user)}
                                        aria-label={`Eliminar a ${user.name}`}
                                    >
                                        <Trash2 />
                                    </Button>
                                )}
                            </div>
                        </li>
                    ))}
                </ul>
            </div>

            <UserDialog
                user={editing}
                roles={roles}
                open={open}
                onClose={() => setOpen(false)}
            />
        </>
    );
}

UsersIndex.layout = {
    breadcrumbs: [{ title: 'Usuarios', href: index() }],
};

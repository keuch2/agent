import { Form, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { RouteDefinition, RouteFormDefinition } from '@/wayfinder';

export type WhatsAppChannel = {
    phone_number_id: string | null;
    waba_id: string | null;
    display_phone_number: string | null;
    verified_name: string | null;
    status: 'pending' | 'connected' | 'error' | 'disconnected';
    status_label: string;
    last_error: string | null;
    verified_at: string | null;
    has_token: boolean;
};

export const channelStatusVariant = {
    pending: 'secondary',
    connected: 'default',
    error: 'destructive',
    disconnected: 'secondary',
} as const;

export function WhatsAppChannelForm({
    channel,
    update,
    verify,
    disconnect,
}: {
    channel: WhatsAppChannel | null;
    update: RouteFormDefinition<'post'>;
    verify: RouteDefinition<'post'>;
    disconnect: RouteDefinition<'delete'>;
}) {
    const isLinked =
        channel !== null &&
        channel.status !== 'disconnected' &&
        channel.has_token;

    return (
        <div className="max-w-2xl space-y-6">
            {channel && (
                <div className="flex flex-wrap items-center gap-3 rounded-xl border p-4 text-sm">
                    <Badge variant={channelStatusVariant[channel.status]}>
                        {channel.status_label}
                    </Badge>
                    {channel.display_phone_number && (
                        <span className="font-medium tabular-nums">
                            {channel.display_phone_number}
                        </span>
                    )}
                    {channel.verified_name && (
                        <span className="text-muted-foreground">
                            {channel.verified_name}
                        </span>
                    )}
                    {channel.verified_at && (
                        <span className="text-muted-foreground">
                            Verificado el{' '}
                            {new Date(channel.verified_at).toLocaleString(
                                'es-PY',
                            )}
                        </span>
                    )}
                </div>
            )}

            {channel?.last_error && (
                <Alert variant="destructive">
                    <AlertDescription>{channel.last_error}</AlertDescription>
                </Alert>
            )}

            <Form
                {...update}
                className="space-y-6"
                options={{ preserveScroll: true }}
                resetOnSuccess={['access_token']}
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="phone_number_id">
                                Identificador del número (Phone Number ID)
                            </Label>
                            <Input
                                id="phone_number_id"
                                name="phone_number_id"
                                inputMode="numeric"
                                autoComplete="off"
                                defaultValue={channel?.phone_number_id ?? ''}
                                required
                            />
                            <InputError message={errors.phone_number_id} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="waba_id">
                                Identificador de la cuenta (WhatsApp Business
                                Account ID)
                            </Label>
                            <Input
                                id="waba_id"
                                name="waba_id"
                                inputMode="numeric"
                                autoComplete="off"
                                defaultValue={channel?.waba_id ?? ''}
                                required
                            />
                            <p className="text-xs text-muted-foreground">
                                Los dos identificadores están en la app de Meta,
                                en WhatsApp → Configuración de la API.
                            </p>
                            <InputError message={errors.waba_id} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="access_token">
                                Token de acceso
                            </Label>
                            <Input
                                id="access_token"
                                name="access_token"
                                type="password"
                                autoComplete="new-password"
                                placeholder={
                                    isLinked
                                        ? 'Guardado. Dejalo vacío para conservarlo'
                                        : undefined
                                }
                                required={!isLinked}
                            />
                            <p className="text-xs text-muted-foreground">
                                Se guarda cifrado y no se vuelve a mostrar.
                            </p>
                            <InputError message={errors.access_token} />
                        </div>

                        <Button disabled={processing}>
                            {processing
                                ? 'Verificando con Meta…'
                                : 'Guardar y conectar'}
                        </Button>
                    </>
                )}
            </Form>

            {isLinked && (
                <div className="flex flex-wrap gap-3 border-t pt-6">
                    <Button variant="outline" asChild>
                        <Link href={verify} as="button" preserveScroll>
                            Probar conexión
                        </Link>
                    </Button>

                    <Dialog>
                        <DialogTrigger asChild>
                            <Button variant="destructive">Desconectar</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogTitle>¿Desconectar WhatsApp?</DialogTitle>
                            <DialogDescription>
                                El agente dejará de responder en este número y
                                se borrará el token. El historial se conserva.
                            </DialogDescription>
                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button variant="secondary">
                                        Cancelar
                                    </Button>
                                </DialogClose>
                                <Button variant="destructive" asChild>
                                    <Link
                                        href={disconnect}
                                        as="button"
                                        preserveScroll
                                    >
                                        Desconectar
                                    </Link>
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </div>
            )}
        </div>
    );
}

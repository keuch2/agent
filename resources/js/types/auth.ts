export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    is_superadmin: boolean;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
    tenant: { id: number; name: string } | null;
    support: { id: number; name: string } | null;
    can: { manageUsers?: boolean; manageChannels?: boolean };
};

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};

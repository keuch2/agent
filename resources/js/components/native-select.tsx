import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

type Option = { value: string | number; label: string };

/**
 * Native <select> styled like <Input>, so it submits with Inertia <Form>.
 */
export function NativeSelect({
    options,
    placeholder,
    className,
    ...props
}: ComponentProps<'select'> & { options: Option[]; placeholder?: string }) {
    return (
        <select
            className={cn(
                'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive md:text-sm dark:bg-input/30',
                className,
            )}
            {...props}
        >
            {placeholder && <option value="">{placeholder}</option>}
            {options.map((option) => (
                <option key={option.value} value={option.value}>
                    {option.label}
                </option>
            ))}
        </select>
    );
}

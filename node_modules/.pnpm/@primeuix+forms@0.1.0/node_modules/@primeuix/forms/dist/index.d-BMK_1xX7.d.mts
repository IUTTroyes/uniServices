interface ResolverOptions {
    sync?: boolean;
    raw?: boolean;
}

interface ResolverResult<T> {
    values: T;
    errors: Record<string, any>;
}

export type { ResolverOptions as R, ResolverResult as a };

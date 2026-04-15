import { Schema, ParseParams } from 'zod';
import { R as ResolverOptions, a as ResolverResult } from '../../index.d-BMK_1xX7.mjs';

declare const zodResolver: <T extends Schema<any, any>>(schema: T, schemaOptions?: ParseParams, resolverOptions?: ResolverOptions) => ({ values, name }: any) => Promise<ResolverResult<T>>;

export { zodResolver };

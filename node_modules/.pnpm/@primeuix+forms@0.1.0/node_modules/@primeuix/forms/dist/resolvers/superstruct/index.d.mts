import { Struct } from 'superstruct';
import { R as ResolverOptions, a as ResolverResult } from '../../index.d-BMK_1xX7.mjs';

declare const superStructResolver: <T>(schema: Struct<T>, schemaOptions?: any, resolverOptions?: ResolverOptions) => ({ values, name }: any) => Promise<ResolverResult<T>>;

export { superStructResolver };

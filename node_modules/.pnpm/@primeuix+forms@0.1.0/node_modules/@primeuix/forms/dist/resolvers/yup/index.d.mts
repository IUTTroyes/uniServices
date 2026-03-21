import { AnyObjectSchema, ValidateOptions } from 'yup';
import { R as ResolverOptions, a as ResolverResult } from '../../index.d-BMK_1xX7.mjs';

declare const yupResolver: <T>(schema: AnyObjectSchema, schemaOptions?: ValidateOptions<any>, resolverOptions?: ResolverOptions) => ({ values, name }: any) => Promise<ResolverResult<T>>;

export { yupResolver };

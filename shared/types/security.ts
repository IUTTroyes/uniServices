export interface SecurityDepartmentContext {
  id: number
  code: string
  label: string
  packages: string[]
  permissions: string[]
}

export interface SecurityContext {
  user: {
    id: number
    displayName: string
    type: 'personnel' | 'student'
  }
  currentDepartment: string | null
  departments: SecurityDepartmentContext[]
}

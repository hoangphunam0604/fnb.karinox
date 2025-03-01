export interface User {
  id: number;
  fullname: string;
  role: string;
  permissions: string[];
  current_branch?: number | null;
}

export type PageProps<
  T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
  auth: {
    user: User;
  };
};

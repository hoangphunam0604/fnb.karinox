export interface User {
  id: number;
  name: string;
  email: string;
  role: string; // Nếu chỉ có một số vai trò cố định, có thể dùng union type: 'admin' | 'kitchen' | 'pos'
  permissions: string[]; // Danh sách các quyền hạn (permissions)
  current_branch?: number | null; // Chi nhánh hiện tại, optional vì có thể null
}

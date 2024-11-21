import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { useQuery } from "@tanstack/react-query";
import { Loader2 } from "lucide-react";

interface ActiveUser {
  id: string;
  name: string;
  currentPage: string;
  duration: string;
  lastActive: string;
}

// In a real app, this would fetch from your backend
const fetchActiveUsers = async (): Promise<ActiveUser[]> => {
  // Simulated API call
  return [
    {
      id: "1",
      name: "Guest User 1",
      currentPage: "/products",
      duration: "5m 30s",
      lastActive: "Just now"
    },
    {
      id: "2",
      name: "John Doe",
      currentPage: "/dashboard",
      duration: "15m 45s",
      lastActive: "2m ago"
    },
    {
      id: "3",
      name: "Guest User 2",
      currentPage: "/about",
      duration: "1m 20s",
      lastActive: "Just now"
    }
  ];
};

export const ActiveUsersTable = () => {
  const { data: users, isLoading } = useQuery({
    queryKey: ['activeUsers'],
    queryFn: fetchActiveUsers,
    refetchInterval: 5000 // Refetch every 5 seconds to keep data real-time
  });

  if (isLoading) {
    return (
      <div className="flex justify-center items-center p-8">
        <Loader2 className="h-8 w-8 animate-spin" />
      </div>
    );
  }

  return (
    <Table>
      <TableHeader>
        <TableRow>
          <TableHead>User</TableHead>
          <TableHead>Current Page</TableHead>
          <TableHead>Duration</TableHead>
          <TableHead>Last Active</TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        {users?.map((user) => (
          <TableRow key={user.id}>
            <TableCell className="font-medium">{user.name}</TableCell>
            <TableCell>{user.currentPage}</TableCell>
            <TableCell>{user.duration}</TableCell>
            <TableCell>{user.lastActive}</TableCell>
          </TableRow>
        ))}
      </TableBody>
    </Table>
  );
};
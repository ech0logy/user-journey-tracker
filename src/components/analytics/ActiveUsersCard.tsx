import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Users } from "lucide-react";

interface ActiveUsersCardProps {
  count: number;
}

export const ActiveUsersCard = ({ count }: ActiveUsersCardProps) => {
  return (
    <Card className="animate-fade-in">
      <CardHeader className="flex flex-row items-center justify-between pb-2">
        <CardTitle className="text-sm font-medium">Active Users</CardTitle>
        <Users className="h-4 w-4 text-analytics-blue" />
      </CardHeader>
      <CardContent>
        <div className="text-2xl font-bold text-analytics-blue">{count}</div>
        <p className="text-xs text-muted-foreground">users online now</p>
      </CardContent>
    </Card>
  );
};
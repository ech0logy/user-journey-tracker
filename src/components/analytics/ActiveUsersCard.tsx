import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Users } from "lucide-react";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { ActiveUsersTable } from "./ActiveUsersTable";

interface ActiveUsersCardProps {
  count: number;
}

export const ActiveUsersCard = ({ count }: ActiveUsersCardProps) => {
  return (
    <Dialog>
      <DialogTrigger asChild>
        <Card className="animate-fade-in hover:bg-accent/50 cursor-pointer transition-colors">
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Active Users</CardTitle>
            <Users className="h-4 w-4 text-analytics-blue" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-analytics-blue">{count}</div>
            <p className="text-xs text-muted-foreground">users online now</p>
          </CardContent>
        </Card>
      </DialogTrigger>
      <DialogContent className="max-w-3xl">
        <DialogHeader>
          <DialogTitle>Active Users</DialogTitle>
        </DialogHeader>
        <ActiveUsersTable />
      </DialogContent>
    </Dialog>
  );
};
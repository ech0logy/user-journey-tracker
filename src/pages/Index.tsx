import { ActiveUsersCard } from "@/components/analytics/ActiveUsersCard";
import { SessionDurationChart } from "@/components/analytics/SessionDurationChart";
import { PageVisitsTable } from "@/components/analytics/PageVisitsTable";

// Mock data - in a real app this would come from your backend
const mockSessionData = [
  { time: "00:00", duration: 45 },
  { time: "01:00", duration: 30 },
  { time: "02:00", duration: 60 },
  { time: "03:00", duration: 35 },
  { time: "04:00", duration: 50 },
  { time: "05:00", duration: 55 },
];

const mockPageVisits = [
  { id: "1", path: "/home", timestamp: "2024-02-20 14:30", duration: "5m 30s" },
  { id: "2", path: "/products", timestamp: "2024-02-20 14:25", duration: "2m 15s" },
  { id: "3", path: "/about", timestamp: "2024-02-20 14:20", duration: "1m 45s" },
  { id: "4", path: "/contact", timestamp: "2024-02-20 14:15", duration: "3m 20s" },
];

const Index = () => {
  return (
    <div className="min-h-screen bg-background p-8">
      <div className="mx-auto max-w-7xl space-y-8">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Analytics Dashboard</h1>
          <p className="text-muted-foreground">Track your website's real-time user activity</p>
        </div>
        
        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
          <ActiveUsersCard count={42} />
          <div className="md:col-span-2">
            <SessionDurationChart data={mockSessionData} />
          </div>
        </div>
        
        <div className="grid gap-6">
          <PageVisitsTable visits={mockPageVisits} />
        </div>
      </div>
    </div>
  );
};

export default Index;
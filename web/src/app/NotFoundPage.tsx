import { Compass } from 'lucide-react';
import { Link } from 'react-router-dom';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/States';

export function NotFoundPage() {
  return (
    <div className="flex min-h-screen items-center justify-center bg-canvas">
      <EmptyState
        icon={<Compass className="size-7" />}
        title="الصفحة غير موجودة"
        description="الرابط الذي فتحته غير صحيح أو أن الصفحة نُقلت."
        action={
          <Link to="/">
            <Button>العودة للرئيسية</Button>
          </Link>
        }
      />
    </div>
  );
}

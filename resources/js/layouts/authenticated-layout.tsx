import AppLayout from '@/layouts/app-layout';
import { type ReactNode } from 'react';

interface AuthenticatedLayoutProps {
    children: ReactNode;
}

export default function AuthenticatedLayout({ children }: AuthenticatedLayoutProps) {
    return (
        <AppLayout>
            <div className="p-6">
                {children}
            </div>
        </AppLayout>
    );
}
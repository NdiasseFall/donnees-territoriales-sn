'use client';

import { MapComponent } from '@/components/map/MapComponent';
import { SidePanel } from '@/components/ui/SidePanel';
import { Header } from '@/components/ui/Header';
import { useMapStore } from '@/lib/store';

export default function HomePage() {
  return (
    <div className="h-screen flex flex-col">
      <Header />
      <div className="flex-1 flex overflow-hidden">
        <MapComponent />
        <SidePanel />
      </div>
    </div>
  );
}
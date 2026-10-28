import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { MapPin, Search } from 'lucide-react';
import { useEffect, useState } from 'react';
import { MapContainer, Marker, TileLayer, useMap, useMapEvents } from 'react-leaflet';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/FormField';

export interface PickedLocation {
  location: string;
  latitude: number;
  longitude: number;
}

interface LocationPickerProps {
  value: PickedLocation | null;
  onChange: (value: PickedLocation) => void;
  error?: string;
}

interface NominatimResult {
  display_name: string;
  lat: string;
  lon: string;
}

// دمشق — مركز الخريطة الافتراضي قبل اختيار موقع
const DEFAULT_CENTER: [number, number] = [33.5138, 36.2765];

// أيقونة مرسومة بـ CSS بدل صور Leaflet الافتراضية (التي لا تُحمَّل مع أدوات البناء دون إعداد)
const pinIcon = L.divIcon({
  className: '',
  iconSize: [28, 28],
  iconAnchor: [14, 28],
  html: '<div style="width:28px;height:28px;border-radius:50% 50% 50% 0;background:#dc7a32;border:3px solid #fff;box-shadow:0 2px 8px rgb(0 0 0 / .35);transform:rotate(-45deg)"></div>',
});

/**
 * اختيار موقع الفعالية: بحث بالاسم (OpenStreetMap Nominatim) أو نقر مباشر على الخريطة.
 */
export function LocationPicker({ value, onChange, error }: LocationPickerProps) {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState<NominatimResult[]>([]);
  const [searching, setSearching] = useState(false);
  const [searchError, setSearchError] = useState<string | null>(null);

  const search = async () => {
    const term = query.trim();
    if (term.length < 3) return;

    setSearching(true);
    setSearchError(null);

    try {
      const response = await fetch(
        `https://nominatim.openstreetmap.org/search?format=json&limit=5&accept-language=ar&q=${encodeURIComponent(term)}`,
      );
      const data = (await response.json()) as NominatimResult[];

      setResults(data);
      if (data.length === 0) setSearchError('لم يُعثر على نتائج. جرّب اسماً آخر أو انقر على الخريطة.');
    } catch {
      setSearchError('تعذّر البحث. يمكنك تحديد الموقع بالنقر على الخريطة.');
    } finally {
      setSearching(false);
    }
  };

  const pick = (result: NominatimResult) => {
    onChange({ location: result.display_name, latitude: Number(result.lat), longitude: Number(result.lon) });
    setResults([]);
    setQuery('');
  };

  const pickFromMap = async (latitude: number, longitude: number) => {
    // نحدّد الموقع فوراً، ثم نحاول جلب اسمه
    onChange({ location: value?.location || `${latitude.toFixed(5)}, ${longitude.toFixed(5)}`, latitude, longitude });

    try {
      const response = await fetch(
        `https://nominatim.openstreetmap.org/reverse?format=json&accept-language=ar&lat=${latitude}&lon=${longitude}`,
      );
      const data = (await response.json()) as { display_name?: string };

      if (data.display_name) onChange({ location: data.display_name, latitude, longitude });
    } catch {
      // الإحداثيات كافية حتى لو فشل جلب الاسم
    }
  };

  return (
    <div className="flex flex-col gap-3">
      <div className="flex gap-2">
        <Input
          wrapperClassName="flex-1"
          aria-label="بحث عن موقع"
          placeholder="ابحث عن مدينة أو عنوان…"
          value={query}
          onChange={(event) => setQuery(event.target.value)}
          onKeyDown={(event) => {
            if (event.key === 'Enter') {
              event.preventDefault();
              void search();
            }
          }}
        />
        <Button variant="outline" icon={<Search className="size-4" />} loading={searching} onClick={search}>
          بحث
        </Button>
      </div>

      {results.length > 0 && (
        <ul className="divide-y divide-line overflow-hidden rounded-xl border border-line">
          {results.map((result) => (
            <li key={`${result.lat}-${result.lon}`}>
              <button
                type="button"
                onClick={() => pick(result)}
                className="flex w-full items-start gap-2 px-3 py-2.5 text-start text-sm text-ink-soft hover:bg-brand-50"
              >
                <MapPin className="mt-0.5 size-4 shrink-0 text-accent-500" />
                {result.display_name}
              </button>
            </li>
          ))}
        </ul>
      )}

      {searchError && <p className="text-xs text-muted">{searchError}</p>}

      <div className="h-64 overflow-hidden rounded-xl border border-line">
        <MapContainer center={value ? [value.latitude, value.longitude] : DEFAULT_CENTER} zoom={value ? 14 : 11} className="size-full" scrollWheelZoom={false}>
          <TileLayer attribution="&copy; OpenStreetMap" url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" />
          <ClickHandler onPick={pickFromMap} />
          {value && (
            <>
              <Marker position={[value.latitude, value.longitude]} icon={pinIcon} />
              <Recenter latitude={value.latitude} longitude={value.longitude} />
            </>
          )}
        </MapContainer>
      </div>

      {value ? (
        <p className="flex items-start gap-2 rounded-xl bg-brand-50 px-3 py-2.5 text-sm text-brand-800">
          <MapPin className="mt-0.5 size-4 shrink-0" />
          {value.location}
        </p>
      ) : (
        <p className="text-xs text-muted">انقر على الخريطة لتحديد الموقع بدقة.</p>
      )}

      {error && (
        <p role="alert" className="text-xs text-danger-600">
          {error}
        </p>
      )}
    </div>
  );
}

function ClickHandler({ onPick }: { onPick: (latitude: number, longitude: number) => void }) {
  useMapEvents({ click: (event) => onPick(event.latlng.lat, event.latlng.lng) });
  return null;
}

function Recenter({ latitude, longitude }: { latitude: number; longitude: number }) {
  const map = useMap();

  useEffect(() => {
    map.setView([latitude, longitude], Math.max(map.getZoom(), 14));
  }, [map, latitude, longitude]);

  return null;
}

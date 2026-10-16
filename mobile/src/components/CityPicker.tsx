import { Check, ChevronDown, MapPin } from 'lucide-react-native';
import { useState } from 'react';
import { Pressable, View } from 'react-native';

import { useCities } from '@/src/api/hooks';
import { useCity } from '@/src/city';
import { colors, ListItem, Sheet, Text } from '@/src/ui';

/** "📍 София ⌄" in the Programme header; opens a sheet with every city that has theatres. */
export function CityPicker({ onChange }: { onChange?: (city: string) => void }) {
  const { city, setCity } = useCity();
  const { data: cities } = useCities();
  const [open, setOpen] = useState(false);

  if (!cities || cities.length < 2) return null;

  return (
    <>
      <Pressable
        onPress={() => setOpen(true)}
        accessibilityRole="button"
        accessibilityLabel={`Град: ${city}. Смени града`}
        className="flex-row items-center gap-1.5 rounded-full border border-border px-3 py-2 active:bg-card"
      >
        <MapPin size={15} color={colors.gold} />
        <Text className="font-body-medium">{city}</Text>
        <ChevronDown size={15} color={colors.muted} />
      </Pressable>
      <Sheet visible={open} onClose={() => setOpen(false)} title="Град">
        <View className="-mx-5 pb-2">
          {cities.map((c) => (
            <ListItem
              key={c.name}
              title={c.name}
              description={`${plural(c.theatres_count, 'театър', 'театъра')} · ${plural(c.upcoming_count, 'представление', 'представления')}`}
              icon={MapPin}
              accessibilityState={{ selected: c.name === city }}
              right={c.name === city ? <Check size={18} color={colors.primary} /> : <View />}
              onPress={() => {
                setOpen(false);
                if (c.name !== city) {
                  setCity(c.name);
                  onChange?.(c.name);
                }
              }}
            />
          ))}
        </View>
      </Sheet>
    </>
  );
}

function plural(n: number, one: string, many: string): string {
  return `${n} ${n === 1 ? one : many}`;
}

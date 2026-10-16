import { Check, ChevronDown, MapPin } from 'lucide-react-native';
import { useState } from 'react';
import { ActivityIndicator, Pressable, View } from 'react-native';

import { useCities } from '@/src/api/hooks';
import { useCity } from '@/src/city';
import { Button, colors, ListItem, Sheet, Text } from '@/src/ui';

/** "📍 София ⌄" in the Programme header; opens a sheet with every city that has theatres. Always shown, so a failed load can't hide it. */
export function CityPicker({ onChange }: { onChange?: (city: string) => void }) {
  const { city, setCity } = useCity();
  const { data: cities, isError, refetch } = useCities();
  const [open, setOpen] = useState(false);

  return (
    <>
      <Pressable
        onPress={() => {
          setOpen(true);
          refetch();
        }}
        accessibilityRole="button"
        accessibilityLabel={`Град: ${city}. Смени града`}
        className="flex-row items-center gap-1.5 rounded-full border border-border px-3 py-2 active:bg-card"
      >
        <MapPin size={15} color={colors.gold} />
        <Text className="font-body-medium">{city}</Text>
        <ChevronDown size={15} color={colors.muted} />
      </Pressable>
      <Sheet visible={open} onClose={() => setOpen(false)} title="Град">
        {!cities && !isError && <ActivityIndicator className="p-6" color={colors.primary} />}
        {isError && !cities && (
          <View className="items-start gap-3 pb-4">
            <Text variant="muted">Списъкът с градове не се зареди.</Text>
            <Button variant="secondary" size="sm" onPress={() => refetch()}>
              Опитай пак
            </Button>
          </View>
        )}
        <View className="-mx-5 pb-2">
          {(cities ?? []).map((c) => (
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

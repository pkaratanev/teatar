import { MaterialCommunityIcons } from '@expo/vector-icons';
import { Tabs } from 'expo-router';
import type { ComponentProps } from 'react';
import { useTheme } from 'react-native-paper';

type IconName = ComponentProps<typeof MaterialCommunityIcons>['name'];

const icon =
  (name: IconName) =>
  ({ color, size }: { color: string; size: number }) => <MaterialCommunityIcons name={name} color={color} size={size} />;

export default function TabsLayout() {
  const theme = useTheme();

  return (
    <Tabs screenOptions={{ tabBarActiveTintColor: theme.colors.primary }}>
      <Tabs.Screen name="index" options={{ title: 'Програма', tabBarIcon: icon('drama-masks') }} />
      <Tabs.Screen name="search" options={{ title: 'Търсене', tabBarIcon: icon('magnify') }} />
      <Tabs.Screen name="diary" options={{ title: 'Дневник', tabBarIcon: icon('notebook-outline') }} />
      <Tabs.Screen name="profile" options={{ title: 'Профил', tabBarIcon: icon('account-circle-outline') }} />
    </Tabs>
  );
}

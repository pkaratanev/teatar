import { Tabs } from 'expo-router';
import { BookOpen, Drama, Search, User, Users, type LucideIcon } from 'lucide-react-native';

import { colors } from '@/src/ui';

const icon =
  (Icon: LucideIcon) =>
  ({ color, focused }: { color: string; focused: boolean }) => <Icon size={22} color={color} strokeWidth={focused ? 2.25 : 1.75} />;

export default function TabsLayout() {
  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: colors.foreground,
        tabBarInactiveTintColor: colors.faint,
        tabBarLabelStyle: { fontFamily: 'Inter_500Medium', fontSize: 11 },
        tabBarStyle: { backgroundColor: colors.background, borderTopColor: colors.border },
        sceneStyle: { backgroundColor: colors.background },
      }}
    >
      <Tabs.Screen name="index" options={{ title: 'Програма', tabBarIcon: icon(Drama) }} />
      <Tabs.Screen name="search" options={{ title: 'Търсене', tabBarIcon: icon(Search) }} />
      <Tabs.Screen name="feed" options={{ title: 'Лента', tabBarIcon: icon(Users) }} />
      <Tabs.Screen name="diary" options={{ title: 'Дневник', tabBarIcon: icon(BookOpen) }} />
      <Tabs.Screen name="profile" options={{ title: 'Профил', tabBarIcon: icon(User) }} />
    </Tabs>
  );
}

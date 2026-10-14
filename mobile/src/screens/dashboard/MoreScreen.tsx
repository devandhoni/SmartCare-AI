import React from "react";
import {
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";

import { useAuth } from "../../context/AuthContext";
import {
  getVisibleMoreMenu,
  type MobileModule,
} from "../../navigation/menuConfig";

interface MoreScreenProps {
  onOpenModule: (module: MobileModule) => void;
}

export default function MoreScreen({
  onOpenModule,
}: MoreScreenProps) {
  const { user, logout } = useAuth();

  if (!user) {
    return null;
  }

  const sections = getVisibleMoreMenu(user.role);

  return (
    <SafeAreaView style={styles.safeArea} edges={["top", "bottom"]}>
      <ScrollView
        contentContainerStyle={styles.content}
        showsVerticalScrollIndicator={false}
      >
        <View style={styles.header}>
          <Text style={styles.title}>More</Text>

          <Text style={styles.subtitle}>
            SmartCare-AI modules and management tools
          </Text>

          <Text style={styles.user}>
            {user.full_name || user.name || user.email}
          </Text>

          <Text style={styles.role}>
            {user.role}
          </Text>
        </View>

        {sections.map((section, sectionIndex) => (
          <View
            key={section.title || `main-${sectionIndex}`}
            style={styles.section}
          >
            {section.title ? (
              <Text style={styles.sectionTitle}>
                {section.title}
              </Text>
            ) : null}

            <View style={styles.card}>
              {section.items.map((item, itemIndex) => (
                <Pressable
                  key={item.route}
                  onPress={() => onOpenModule(item.route)}
                  style={({ pressed }) => [
                    styles.menuItem,
                    itemIndex < section.items.length - 1 &&
                      styles.menuItemBorder,
                    pressed && styles.menuItemPressed,
                  ]}
                >
                  <Text style={styles.menuItemText}>
                    {item.name}
                  </Text>

                  <Text style={styles.chevron}>›</Text>
                </Pressable>
              ))}
            </View>
          </View>
        ))}

        <Pressable
          onPress={logout}
          style={({ pressed }) => [
            styles.logoutButton,
            pressed && styles.logoutPressed,
          ]}
        >
          <Text style={styles.logoutText}>
            Logout
          </Text>
        </Pressable>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: "#f1f5f9",
  },

  content: {
    padding: 18,
    paddingBottom: 32,
  },

  header: {
    marginBottom: 24,
  },

  title: {
    fontSize: 30,
    fontWeight: "700",
    color: "#0f172a",
  },

  subtitle: {
    marginTop: 5,
    fontSize: 14,
    lineHeight: 20,
    color: "#64748b",
  },

  user: {
    marginTop: 16,
    fontSize: 16,
    fontWeight: "600",
    color: "#334155",
  },

  role: {
    marginTop: 3,
    fontSize: 13,
    color: "#64748b",
  },

  section: {
    marginBottom: 22,
  },

  sectionTitle: {
    marginBottom: 8,
    marginLeft: 4,
    fontSize: 12,
    fontWeight: "700",
    textTransform: "uppercase",
    letterSpacing: 1,
    color: "#64748b",
  },

  card: {
    overflow: "hidden",
    borderRadius: 14,
    backgroundColor: "#ffffff",
  },

  menuItem: {
    minHeight: 56,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    paddingHorizontal: 16,
  },

  menuItemBorder: {
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderBottomColor: "#e2e8f0",
  },

  menuItemPressed: {
    backgroundColor: "#f8fafc",
  },

  menuItemText: {
    flex: 1,
    fontSize: 16,
    fontWeight: "600",
    color: "#1e293b",
  },

  chevron: {
    marginLeft: 12,
    fontSize: 26,
    color: "#94a3b8",
  },

  logoutButton: {
    minHeight: 52,
    alignItems: "center",
    justifyContent: "center",
    borderRadius: 12,
    borderWidth: 1,
    borderColor: "#fecaca",
    backgroundColor: "#ffffff",
  },

  logoutPressed: {
    backgroundColor: "#fef2f2",
  },

  logoutText: {
    fontSize: 16,
    fontWeight: "700",
    color: "#b91c1c",
  },
});
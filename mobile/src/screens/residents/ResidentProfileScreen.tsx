import React, {
  useCallback,
  useEffect,
  useState,
} from "react";
import {
  ActivityIndicator,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import { useRoute } from "@react-navigation/native";

import { getResidentProfile } from "../../api/residentApi";
import { useNavigation } from "@react-navigation/native";
import type {
  ResidentProfileResponse,
} from "../../types/resident";

export default function ResidentProfileScreen() {
  const route = useRoute<any>();
  const navigation = useNavigation<any>();
  const residentId = Number(route.params?.residentId);

  const [profile, setProfile] =
    useState<ResidentProfileResponse | null>(null);

  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState("");

  const loadProfile = useCallback(
    async (isRefresh = false) => {
      if (!residentId) {
        setError("Resident information is unavailable.");
        setLoading(false);
        return;
      }

      try {
        if (isRefresh) {
          setRefreshing(true);
        } else {
          setLoading(true);
        }

        setError("");

        const result =
          await getResidentProfile(residentId);

        setProfile(result);
      } catch (requestError: any) {
        console.error(
          "Failed to load resident profile:",
          requestError
        );

        setProfile(null);

        if (
          requestError?.response?.status === 404
        ) {
          setError("Resident not found.");
        } else {
          setError(
            "Unable to load resident information. Please try again."
          );
        }
      } finally {
        setLoading(false);
        setRefreshing(false);
      }
    },
    [residentId]
  );

  useEffect(() => {
    loadProfile();
  }, [loadProfile]);

  if (loading) {
    return (
      <SafeAreaView
        style={styles.safeArea}
        edges={["bottom"]}
      >
        <View style={styles.centerState}>
          <ActivityIndicator size="large" />

          <Text style={styles.stateText}>
            Loading resident...
          </Text>
        </View>
      </SafeAreaView>
    );
  }

  if (error || !profile?.resident) {
    return (
      <SafeAreaView
        style={styles.safeArea}
        edges={["bottom"]}
      >
        <View style={styles.centerState}>
          <Text style={styles.errorTitle}>
            Unable to open resident
          </Text>

          <Text style={styles.stateText}>
            {error ||
              "Resident information is unavailable."}
          </Text>

          <Pressable
            style={styles.retryButton}
            onPress={() => loadProfile()}
          >
            <Text style={styles.retryButtonText}>
              Try Again
            </Text>
          </Pressable>
        </View>
      </SafeAreaView>
    );
  }

  const resident = profile.resident;

  const name =
    resident.full_name ||
    resident.name ||
    "Unnamed Resident";

  const room =
    resident.room_number ||
    resident.room ||
    "Room not assigned";

  const initials = name
    .split(" ")
    .filter(Boolean)
    .map((part) => part[0])
    .slice(0, 2)
    .join("")
    .toUpperCase();

  return (
    <SafeAreaView
      style={styles.safeArea}
      edges={["bottom"]}
    >
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => loadProfile(true)}
          />
        }
      >
        <View style={styles.identityCard}>
          <View style={styles.avatar}>
            <Text style={styles.avatarText}>
              {initials || "R"}
            </Text>
          </View>

          <View style={styles.identityInfo}>
            <Text style={styles.name}>
              {name}
            </Text>

            <Text style={styles.room}>
              {room}
            </Text>

            <View style={styles.badges}>
              {resident.gender ? (
                <Badge
                  text={String(resident.gender)}
                />
              ) : null}

              <Badge
                text={String(
                  resident.status || "Active"
                )}
              />
            </View>
          </View>
        </View>
        

        <Pressable
            style={styles.clinicalButton}
            onPress={() =>
                navigation.navigate("ResidentVitals", {
                residentId,
                })
            }
            >
            <View style={styles.clinicalButtonText}>
                <Text style={styles.clinicalButtonTitle}>
                Vital Signs
                </Text>

                <Text style={styles.clinicalButtonSubtitle}>
                View latest readings and vital history
                </Text>
            </View>

            <Text style={styles.clinicalButtonArrow}>
                ›
            </Text>
        </Pressable>

        <Pressable style={styles.clinicalButton} onPress={() => navigation.navigate("ResidentTimeline", {residentId})}>
          <View style={styles.clinicalButtonText}><Text style={styles.clinicalButtonTitle}>Clinical Timeline</Text><Text style={styles.clinicalButtonSubtitle}>Review resident care history</Text></View><Text style={styles.clinicalButtonArrow}>{"\u203A"}</Text>
        </Pressable>
        <Pressable style={styles.clinicalButton} onPress={() => navigation.navigate("ResidentDocuments", {residentId})}>
          <View style={styles.clinicalButtonText}><Text style={styles.clinicalButtonTitle}>Resident Documents</Text><Text style={styles.clinicalButtonSubtitle}>View and manage resident files</Text></View><Text style={styles.clinicalButtonArrow}>{"\u203A"}</Text>
        </Pressable>
        <Section title="Resident Overview">
          <InfoRow
            label="Date of Birth"
            value={resident.date_of_birth}
          />

          <InfoRow
            label="Admission Date"
            value={resident.admission_date}
          />

          <InfoRow
            label="Nationality"
            value={resident.nationality}
          />

          <InfoRow
            label="Blood Group"
            value={resident.blood_group}
          />

          <InfoRow
            label="Mobility"
            value={resident.mobility_status}
          />
        </Section>

        <Section title="Clinical Information">
          <InfoRow
            label="Primary Diagnosis"
            value={
              resident.medical_condition ||
              resident.chronic_disease ||
              "No medical condition recorded"
            }
          />

          <InfoRow
            label="Allergies"
            value={
              resident.allergies ||
              "No allergies recorded"
            }
          />

          <InfoRow
            label="Care Status"
            value={
              profile.active_alerts.length > 0
                ? "Needs Attention"
                : "Stable"
            }
          />

          <InfoRow
            label="Dietary Requirements"
            value={resident.dietary_requirements}
          />
        </Section>

        <View style={styles.summaryGrid}>
          <SummaryCard
            label="Active Alerts"
            value={profile.active_alerts.length}
          />

          <SummaryCard
            label="Pending Tasks"
            value={profile.pending_tasks.length}
          />

          <SummaryCard
            label="Latest Vitals"
            value={
              profile.latest_vital
                ? "Recorded"
                : "None"
            }
          />
        </View>

        <View style={styles.notice}>
          <Text style={styles.noticeTitle}>
            Resident Care Record
          </Text>

          <Text style={styles.noticeText}>
            Vitals, medication, care,
            family and
            other resident workflows will be
            available from this profile as
            their mobile modules are completed.
          </Text>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

function Section({
  title,
  children,
}: {
  title: string;
  children: React.ReactNode;
}) {
  return (
    <View style={styles.section}>
      <Text style={styles.sectionTitle}>
        {title}
      </Text>

      <View style={styles.sectionBody}>
        {children}
      </View>
    </View>
  );
}

function InfoRow({
  label,
  value,
}: {
  label: string;
  value: unknown;
}) {
  const displayValue =
    value === null ||
    value === undefined ||
    String(value).trim() === ""
      ? "Not recorded"
      : String(value);

  return (
    <View style={styles.infoRow}>
      <Text style={styles.infoLabel}>
        {label}
      </Text>

      <Text style={styles.infoValue}>
        {displayValue}
      </Text>
    </View>
  );
}

function Badge({
  text,
}: {
  text: string;
}) {
  return (
    <View style={styles.badge}>
      <Text style={styles.badgeText}>
        {text}
      </Text>
    </View>
  );
}

function SummaryCard({
  label,
  value,
}: {
  label: string;
  value: string | number;
}) {
  return (
    <View style={styles.summaryCard}>
      <Text style={styles.summaryValue}>
        {value}
      </Text>

      <Text style={styles.summaryLabel}>
        {label}
      </Text>
    </View>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: "#f8fafc",
  },

  content: {
    padding: 16,
    paddingBottom: 36,
  },

  centerState: {
    flex: 1,
    alignItems: "center",
    justifyContent: "center",
    padding: 28,
  },

  stateText: {
    marginTop: 10,
    textAlign: "center",
    fontSize: 14,
    lineHeight: 20,
    color: "#64748b",
  },

  errorTitle: {
    fontSize: 18,
    fontWeight: "800",
    color: "#991b1b",
  },

  retryButton: {
    marginTop: 18,
    paddingHorizontal: 18,
    paddingVertical: 11,
    borderRadius: 11,
    backgroundColor: "#2563eb",
  },

  retryButtonText: {
    fontWeight: "700",
    color: "#ffffff",
  },

  identityCard: {
    flexDirection: "row",
    alignItems: "center",
    padding: 18,
    borderWidth: 1,
    borderColor: "#e2e8f0",
    borderRadius: 16,
    backgroundColor: "#ffffff",
  },

  avatar: {
    width: 62,
    height: 62,
    alignItems: "center",
    justifyContent: "center",
    borderRadius: 31,
    backgroundColor: "#dbeafe",
  },

  avatarText: {
    fontSize: 19,
    fontWeight: "800",
    color: "#1d4ed8",
  },

  identityInfo: {
    flex: 1,
    minWidth: 0,
    marginLeft: 15,
  },

  name: {
    fontSize: 21,
    fontWeight: "800",
    color: "#0f172a",
  },

  room: {
    marginTop: 4,
    fontSize: 14,
    color: "#64748b",
  },

  badges: {
    marginTop: 9,
    flexDirection: "row",
    flexWrap: "wrap",
    gap: 6,
  },

  badge: {
    paddingHorizontal: 9,
    paddingVertical: 4,
    borderRadius: 999,
    backgroundColor: "#eff6ff",
  },

  badgeText: {
    fontSize: 10,
    fontWeight: "700",
    color: "#1d4ed8",
  },

  section: {
    marginTop: 14,
    overflow: "hidden",
    borderWidth: 1,
    borderColor: "#e2e8f0",
    borderRadius: 16,
    backgroundColor: "#ffffff",
  },

  sectionTitle: {
    padding: 15,
    borderBottomWidth:
      StyleSheet.hairlineWidth,
    borderBottomColor: "#e2e8f0",
    fontSize: 15,
    fontWeight: "800",
    color: "#0f172a",
  },

  sectionBody: {
    paddingHorizontal: 15,
  },

  infoRow: {
    paddingVertical: 13,
    borderBottomWidth:
      StyleSheet.hairlineWidth,
    borderBottomColor: "#e2e8f0",
  },

  infoLabel: {
    fontSize: 11,
    fontWeight: "700",
    textTransform: "uppercase",
    letterSpacing: 0.5,
    color: "#94a3b8",
  },

  infoValue: {
    marginTop: 4,
    fontSize: 14,
    lineHeight: 20,
    fontWeight: "600",
    color: "#334155",
  },

  summaryGrid: {
    marginTop: 14,
    flexDirection: "row",
    gap: 8,
  },

  summaryCard: {
    flex: 1,
    minHeight: 90,
    alignItems: "center",
    justifyContent: "center",
    padding: 10,
    borderWidth: 1,
    borderColor: "#e2e8f0",
    borderRadius: 14,
    backgroundColor: "#ffffff",
  },

  summaryValue: {
    fontSize: 18,
    fontWeight: "800",
    color: "#0f172a",
  },

  summaryLabel: {
    marginTop: 5,
    textAlign: "center",
    fontSize: 10,
    fontWeight: "700",
    color: "#64748b",
  },

  notice: {
    marginTop: 14,
    padding: 16,
    borderWidth: 1,
    borderColor: "#bfdbfe",
    borderRadius: 14,
    backgroundColor: "#eff6ff",
  },

  noticeTitle: {
    fontSize: 14,
    fontWeight: "800",
    color: "#1e40af",
  },

  noticeText: {
    marginTop: 5,
    fontSize: 12,
    lineHeight: 18,
    color: "#475569",
  },

  clinicalButton: {
    marginTop: 14,
    padding: 16,
    flexDirection: "row",
    alignItems: "center",
    borderWidth: 1,
    borderColor: "#bfdbfe",
    borderRadius: 14,
    backgroundColor: "#eff6ff",
    },

    clinicalButtonText: {
    flex: 1,
    },

    clinicalButtonTitle: {
    fontSize: 15,
    fontWeight: "800",
    color: "#1e40af",
    },

    clinicalButtonSubtitle: {
    marginTop: 3,
    fontSize: 12,
    color: "#64748b",
    },

    clinicalButtonArrow: {
    marginLeft: 10,
    fontSize: 27,
    color: "#2563eb",
    },

});
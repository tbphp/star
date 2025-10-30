// Calculate age from birthday
export const calculateAge = (birthday: string): number => {
  const today = new Date();
  const birthDate = new Date(birthday);
  let age = today.getFullYear() - birthDate.getFullYear();
  const monthDiff = today.getMonth() - birthDate.getMonth();
  if (
    monthDiff < 0 ||
    (monthDiff === 0 && today.getDate() < birthDate.getDate())
  ) {
    age--;
  }
  return age;
};

// Format date
export const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString("zh-CN", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  });
};

// Format datetime
export const formatDateTime = (date: string): string => {
  return new Date(date).toLocaleString("zh-CN", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
  });
};

// Generate star stack display
export const generateStarStack = (
  current: number,
  total: number,
  maxStars: number = 10
): string => {
  if (total === 0) return "";

  const ratio = Math.min(current / total, 1); // Cap at 1 to prevent overflow
  const filledCount = Math.floor(ratio * maxStars);
  const emptyCount = Math.max(0, maxStars - filledCount); // Prevent negative

  return "⭐".repeat(filledCount) + "☆".repeat(emptyCount);
};

// Get gender emoji
export const getGenderEmoji = (gender: "male" | "female"): string => {
  return gender === "male" ? "👦" : "👧";
};

// Validate image file
export const validateImageFile = (
  file: File
): { valid: boolean; error?: string } => {
  const validTypes = ["image/jpeg", "image/png", "image/gif", "image/webp"];
  const maxSize = 100 * 1024 * 1024; // 100MB

  if (!validTypes.includes(file.type)) {
    return { valid: false, error: "只支持 JPG, PNG, GIF, WEBP 格式" };
  }

  if (file.size > maxSize) {
    return { valid: false, error: "图片大小不能超过 100MB" };
  }

  return { valid: true };
};

// Calculate default deduction for reward redemption
export const calculateDefaultDeductions = (
  children: Array<{ id: number; star_count: number }>,
  starCost: number
): Array<{ child_id: number; amount: number }> => {
  const deductions: Array<{ child_id: number; amount: number }> = [];
  const childCount = children.length;

  // Step 1: Calculate average
  const average = Math.floor(starCost / childCount);
  let remaining = starCost;

  // Step 2: Assign to each child (check if they have enough)
  const assignments: Array<{
    child_id: number;
    amount: number;
    shortage: number;
  }> = [];

  for (const child of children) {
    const targetAmount = average;
    if (child.star_count >= targetAmount) {
      assignments.push({
        child_id: child.id,
        amount: targetAmount,
        shortage: 0,
      });
      remaining -= targetAmount;
    } else {
      // Use maximum available
      assignments.push({
        child_id: child.id,
        amount: child.star_count,
        shortage: targetAmount - child.star_count,
      });
      remaining -= child.star_count;
    }
  }

  // Step 3: Distribute remaining to children who have enough
  if (remaining > 0) {
    for (const assignment of assignments) {
      if (assignment.shortage === 0) {
        const child = children.find((c) => c.id === assignment.child_id);
        if (child && child.star_count - assignment.amount >= remaining) {
          assignment.amount += remaining;
          remaining = 0;
          break;
        }
      }
    }
  }

  return assignments.map(({ child_id, amount }) => ({ child_id, amount }));
};

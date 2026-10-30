export type AttributeKey = 'STR' | 'PER' | 'END' | 'CHA' | 'INT' | 'AGI';
export type DifficultyLevel = 'normal' | 'hard';

export type Attributes = Record<AttributeKey, number>;

export interface PerkDefinition {
  id: string;
  name: string;
  description: string;
}

export type ItemType = 'weapon' | 'armor' | 'consumable' | 'ammo' | 'quest';

export interface ItemDefinition {
  id: string;
  name: string;
  type: ItemType;
  description: string;
  value: number;
  damage?: number;
  accuracyBonus?: number;
  apCost?: number;
  isMelee?: boolean;
  ammoType?: string;
  ammoPerShot?: number;
  packSize?: number;
  defense?: number;
  radResistance?: number;
  healHp?: number;
  healRad?: number;
  combatDamage?: number;
  stunChance?: number;
}

export interface CharacterState {
  name: string;
  level: number;
  xp: number;
  pendingLevelUps: number;
  hp: number;
  maxHp: number;
  rad: number;
  caps: number;
  attributes: Attributes;
  perks: string[];
  equippedWeaponId: string | null;
  equippedArmorId: string | null;
  inventory: Record<string, number>;
}

export type BodyPart = 'head' | 'torso' | 'limbs';

export interface EnemyDefinition {
  id: string;
  name: string;
  description: string;
  maxHp: number;
  damage: number;
  accuracy: number;
  defense: number;
  radDamage: number;
  xpReward: number;
  capsReward: number;
  lootItemId?: string;
  ammoDrop?: { ammoId: string; qty: number };
  isBoss?: boolean;
}

export interface CombatState {
  enemyId: string;
  enemyName: string;
  enemyHp: number;
  enemyMaxHp: number;
  enemyStunned: boolean;
  enemyWeakenedTurns: number;
  playerAp: number;
  playerMaxAp: number;
  playerDodgeBonus: number;
  turn: number;
  log: string[];
  victorySceneId: string;
  fleeSceneId?: string;
  isBoss: boolean;
}

export interface SkillCheckSpec {
  attribute: AttributeKey;
  difficulty: number;
}

export interface ChoiceEffect {
  hpDelta?: number;
  radDelta?: number;
  capsDelta?: number;
  xpDelta?: number;
  addItems?: Record<string, number>;
  removeItems?: Record<string, number>;
  setFlags?: Record<string, boolean>;
  incrementCounters?: Record<string, number>;
  setQuestState?: { questId: string; status: 'active' | 'completed' | 'failed'; stage?: string };
  startCombat?: { enemyId: string; victorySceneId: string; fleeSceneId?: string };
  openMerchant?: string;
  openWorldMap?: boolean;
  changeLocation?: LocationId;
}

export interface SceneChoice {
  id: string;
  text: string;
  requiredFlag?: string;
  forbiddenFlag?: string;
  requiredItem?: string;
  requiredCaps?: number;
  minCounter?: { key: string; value: number };
  maxCounter?: { key: string; value: number };
  skillCheck?: SkillCheckSpec;
  nextSceneId: string;
  failSceneId?: string;
  effect?: ChoiceEffect;
  failEffect?: ChoiceEffect;
}

export type LocationId =
  | 'vault_73'
  | 'rusty_crater'
  | 'robco_relay'
  | 'rust_fortress'
  | 'new_reno'
  | 'arroyo'
  | 'ncr'
  | 'vault_13';

export interface SceneDefinition {
  id: string;
  title: string;
  locationId: LocationId;
  description: string;
  dynamicDescription?: (state: GameState) => string;
  onEnterEffect?: ChoiceEffect;
  choices: SceneChoice[];
  isEnding?: boolean;
}

export interface QuestStateEntry {
  status: 'active' | 'completed' | 'failed';
  stage: string;
}

export interface QuestDefinition {
  id: string;
  title: string;
  description: string;
  stages: Record<string, string>;
}

export interface LocationDefinition {
  id: LocationId;
  name: string;
  description: string;
  entrySceneId: string;
  requiredFlag?: string;
}

export interface EncounterDefinition {
  id: string;
  title: string;
  description: string;
  choices: SceneChoice[];
}

export interface MerchantDefinition {
  id: string;
  name: string;
  greeting: string;
  stock: string[];
}

export interface GameState {
  character: CharacterState;
  difficulty?: DifficultyLevel;
  currentLocationId: LocationId;
  currentSceneId: string;
  flags: Record<string, boolean>;
  counters?: Record<string, number>;
  quests: Record<string, QuestStateEntry>;
  combat: CombatState | null;
  activeMerchantId: string | null;
  pendingDestinationSceneId: string | null;
  eventLog: string[];
}

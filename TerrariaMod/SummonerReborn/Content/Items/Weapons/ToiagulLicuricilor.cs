using Microsoft.Xna.Framework;
using SummonerReborn.Content.Buffs;
using SummonerReborn.Content.Projectiles;
using Terraria;
using Terraria.DataStructures;
using Terraria.ID;
using Terraria.ModLoader;

namespace SummonerReborn.Content.Items.Weapons
{
	// Toiag de minion pentru începutul jocului. Cheamă un Licurici (vezi Content/Projectiles/Licurici.cs).
	// Comparație vanilla: Slime Staff 6 damage, Flinx Staff 8, Vampire Frog Staff 11.
	// Cu 11 damage umple golul dintre Flinx Staff și primul boss.
	public class ToiagulLicuricilor : ModItem
	{
		public override void SetStaticDefaults() {
			ItemID.Sets.GamepadWholeScreenUseRange[Type] = true;
			ItemID.Sets.LockOnIgnoresCollision[Type] = true;
			ItemID.Sets.StaffMinionSlotsRequired[Type] = 1f; // ocupă 1 slot de minion
		}

		public override void SetDefaults() {
			Item.damage = 11;
			Item.knockBack = 2f;
			Item.mana = 10;
			Item.width = 32;
			Item.height = 32;
			Item.useTime = 36;
			Item.useAnimation = 36;
			Item.useStyle = ItemUseStyleID.Swing;
			Item.value = Item.sellPrice(silver: 50);
			Item.rare = ItemRarityID.Blue;
			Item.UseSound = SoundID.Item44;

			// Necesare pentru o armă care cheamă minioni
			Item.noMelee = true;
			Item.DamageType = DamageClass.Summon;
			Item.buffType = ModContent.BuffType<LicuriciBuff>();
			Item.shoot = ModContent.ProjectileType<Licurici>();
		}

		public override void ModifyShootStats(Player player, ref Vector2 position, ref Vector2 velocity, ref int type, ref int damage, ref float knockback) {
			// Licuriciul apare la cursor, ca la minionii din vanilla.
			position = Main.MouseWorld;
			player.LimitPointToPlayerReachableArea(ref position);
		}

		public override bool Shoot(Player player, EntitySource_ItemUse_WithAmmo source, Vector2 position, Vector2 velocity, int type, int damage, float knockback) {
			// Buff-ul ține minionul în viață (și îl poți da jos cu click dreapta pe el).
			player.AddBuff(Item.buffType, 2);
			return true;
		}

		public override void AddRecipes() {
			// 12 lemn de orice fel + 3 licurici (prinși cu Bug Net, noaptea), la Work Bench
			CreateRecipe()
				.AddRecipeGroup(RecipeGroupID.Wood, 12)
				.AddIngredient(ItemID.Firefly, 3)
				.AddTile(TileID.WorkBenches)
				.Register();
		}
	}
}

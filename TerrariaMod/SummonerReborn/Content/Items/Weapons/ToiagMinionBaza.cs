using Microsoft.Xna.Framework;
using Terraria;
using Terraria.DataStructures;
using Terraria.ID;
using Terraria.ModLoader;

namespace SummonerReborn.Content.Items.Weapons
{
	// Bază comună pentru toiegele de minion din mod. Construită după ExampleSimpleMinionItem din ExampleMod.
	// Fiecare toiag spune doar ce minion cheamă și ce cifre are (vezi SeteazaToiag).
	public abstract class ToiagMinionBaza : ModItem
	{
		protected abstract int Minion { get; }
		protected abstract int BuffMinion { get; }

		public override void SetStaticDefaults() {
			ItemID.Sets.GamepadWholeScreenUseRange[Type] = true;
			ItemID.Sets.LockOnIgnoresCollision[Type] = true;
			ItemID.Sets.StaffMinionSlotsRequired[Type] = 1f; // ocupă 1 slot de minion
		}

		// Setările comune ale unui toiag de minion. Se apelează din SetDefaults-ul fiecărui toiag.
		protected void SeteazaToiag(int damage, float knockBack, int mana, int useTime, int raritate, int valoare) {
			Item.damage = damage;
			Item.knockBack = knockBack;
			Item.mana = mana;
			Item.width = 32;
			Item.height = 32;
			Item.useTime = useTime;
			Item.useAnimation = useTime;
			Item.useStyle = ItemUseStyleID.Swing;
			Item.value = valoare;
			Item.rare = raritate;
			Item.UseSound = SoundID.Item44;

			// Necesare pentru o armă care cheamă minioni
			Item.noMelee = true;
			Item.DamageType = DamageClass.Summon;
			Item.buffType = BuffMinion;
			Item.shoot = Minion;
		}

		public override void ModifyShootStats(Player player, ref Vector2 position, ref Vector2 velocity, ref int type, ref int damage, ref float knockback) {
			// Minionul apare la cursor, ca la minionii din vanilla.
			position = Main.MouseWorld;
			player.LimitPointToPlayerReachableArea(ref position);
		}

		public override bool Shoot(Player player, EntitySource_ItemUse_WithAmmo source, Vector2 position, Vector2 velocity, int type, int damage, float knockback) {
			// Buff-ul ține minionul în viață (și îl poți da jos cu click dreapta pe el).
			player.AddBuff(Item.buffType, 2);
			return true;
		}
	}
}

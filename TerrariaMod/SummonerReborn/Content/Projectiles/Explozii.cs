using Microsoft.Xna.Framework;
using Terraria;
using Terraria.Audio;
using Terraria.ID;
using Terraria.ModLoader;

namespace SummonerReborn.Content.Projectiles
{
	// Explozii folosite de biciuri și de fulgerele Norișorului.
	// O explozie e un proiectil invizibil, mare, care trăiește câteva frame-uri și lovește tot ce atinge o singură dată.
	// Efectul vizual e făcut din praf (dust) și lumină, așa că nu are nevoie de imagine proprie.
	public abstract class ExplozieBaza : ModProjectile
	{
		protected virtual int Raza => 60;                 // în pixeli (16 pixeli = 1 bloc)
		protected abstract int Praf { get; }
		protected abstract Vector3 Lumina { get; }
		protected virtual SoundStyle Sunet => SoundID.Item14;

		// Nu desenăm nimic, dar tModLoader cere o imagine: folosim una din vanilla.
		public override string Texture => "Terraria/Images/Projectile_" + ProjectileID.LastPrism;

		public override void SetDefaults() {
			Projectile.width = Raza * 2;
			Projectile.height = Raza * 2;
			Projectile.friendly = true;
			Projectile.DamageType = DamageClass.Summon;
			Projectile.penetrate = -1;
			Projectile.timeLeft = 6;
			Projectile.tileCollide = false;
			Projectile.usesLocalNPCImmunity = true;
			Projectile.localNPCHitCooldown = -1; // fiecare inamic e lovit o singură dată
		}

		public override void AI() {
			// Efectele vizuale doar în primul frame.
			if (Projectile.localAI[0] != 0)
				return;

			Projectile.localAI[0] = 1;
			SoundEngine.PlaySound(Sunet, Projectile.Center);
			Lighting.AddLight(Projectile.Center, Lumina * 2f);

			for (int i = 0; i < 30; i++) {
				Vector2 viteza = Main.rand.NextVector2CircularEdge(1f, 1f) * Main.rand.NextFloat(2f, 6f);
				Dust d = Dust.NewDustPerfect(Projectile.Center, Praf, viteza, Scale: Main.rand.NextFloat(1.2f, 1.8f));
				d.noGravity = true;
			}
		}

		public override bool PreDraw(ref Color lightColor) => false;

		// Creează o explozie. Se apelează doar pe calculatorul jucătorului care a lovit (important pentru multiplayer).
		public static void Creeaza<T>(Projectile sursa, Vector2 pozitie, int damage) where T : ExplozieBaza {
			if (Main.myPlayer != sursa.owner || damage <= 0)
				return;

			Projectile.NewProjectile(sursa.GetSource_FromThis(), pozitie, Vector2.Zero, ModContent.ProjectileType<T>(), damage, 4f, sursa.owner);
		}
	}

	// Explozie de foc (Biciul Zmeului).
	public class ExplozieFoc : ExplozieBaza
	{
		protected override int Praf => DustID.Torch;
		protected override Vector3 Lumina => new Vector3(1f, 0.5f, 0.1f);

		public override void OnHitNPC(NPC target, NPC.HitInfo hit, int damageDone) {
			target.AddBuff(BuffID.OnFire3, 180);
		}
	}

	// Explozie de fulger mare (Biciul Solomonarului).
	public class ExplozieFulger : ExplozieBaza
	{
		protected override int Raza => 90;
		protected override int Praf => DustID.Electric;
		protected override Vector3 Lumina => new Vector3(0.5f, 0.7f, 1f);
		protected override SoundStyle Sunet => SoundID.Item94;
	}

	// Explozie de fulger mică (fulgerele Norișorului).
	public class ExplozieFulgerMica : ExplozieBaza
	{
		protected override int Raza => 40;
		protected override int Praf => DustID.Electric;
		protected override Vector3 Lumina => new Vector3(0.5f, 0.7f, 1f);
		protected override SoundStyle Sunet => SoundID.Item93;

		public override void SetStaticDefaults() {
			// Vine de la un minion: primește bonusurile de la bice.
			ProjectileID.Sets.MinionShot[Type] = true;
		}
	}
}

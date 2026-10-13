using Microsoft.Xna.Framework;
using SummonerReborn.Content.Buffs;
using Terraria;
using Terraria.ID;
using Terraria.ModLoader;

namespace SummonerReborn.Content.Projectiles
{
	// Minionul Toiagului Solomonarului: un norișor de furtună care stă deasupra țintei și trage fulgere.
	// (Solomonarii, în folclorul românesc, sunt vrăjitorii care stăpânesc furtunile.)
	public class Norisor : MinionZburatorBaza
	{
		public const int PauzaFulger = 40; // un fulger cam la 2/3 dintr-o secundă

		protected override int BuffMinion => ModContent.BuffType<NorisorBuff>();
		protected override int Latime => 30;
		protected override int Inaltime => 20;
		protected override float VitezaAtac => 14f;
		protected override bool AtacPrinContact => false;
		protected override Vector3 Lumina => new Vector3(0.4f, 0.6f, 1f) * 0.7f;
		protected override int Praf => DustID.Electric;

		// Stă deasupra țintei, nu pe ea.
		protected override Vector2 PozitieAtac(Vector2 tinta) => tinta - new Vector2(0f, 220f);

		private float Cronometru {
			get => Projectile.ai[0];
			set => Projectile.ai[0] = value;
		}

		protected override void Ataca(Vector2 tinta, float distanta) {
			if (++Cronometru < PauzaFulger || distanta > 900f)
				return;

			Cronometru = 0;

			// Doar jucătorul care deține minionul creează proiectilul (important pentru multiplayer).
			if (Main.myPlayer != Projectile.owner)
				return;

			Vector2 viteza = (tinta - Projectile.Center).SafeNormalize(Vector2.UnitY) * 16f;
			Projectile.NewProjectile(Projectile.GetSource_FromThis(), Projectile.Center, viteza,
				ModContent.ProjectileType<Fulger>(), Projectile.damage, Projectile.knockBack, Projectile.owner);
		}
	}

	// Fulgerul tras de Norișor. Explodează la primul inamic lovit (explozie mică de fulger).
	public class Fulger : ModProjectile
	{
		public override void SetStaticDefaults() {
			// Contează ca atac de minion: primește bonusurile de la bice (tag damage).
			ProjectileID.Sets.MinionShot[Type] = true;
		}

		public override void SetDefaults() {
			Projectile.width = 10;
			Projectile.height = 10;
			Projectile.friendly = true;
			Projectile.DamageType = DamageClass.Summon;
			Projectile.penetrate = 3;          // trece prin 3 inamici
			Projectile.timeLeft = 90;
			Projectile.tileCollide = false;
			Projectile.extraUpdates = 1;       // se mișcă de 2 ori pe frame: foarte rapid
			Projectile.usesLocalNPCImmunity = true;
			Projectile.localNPCHitCooldown = -1; // lovește fiecare inamic o singură dată
		}

		public override void OnHitNPC(NPC target, NPC.HitInfo hit, int damageDone) {
			// Doar la prima lovitură (fulgerul trece prin până la 3 inamici).
			if (Projectile.localAI[0] == 0) {
				Projectile.localAI[0] = 1;
				ExplozieBaza.Creeaza<ExplozieFulgerMica>(Projectile, target.Center, Projectile.damage / 2);
			}
		}

		public override void AI() {
			Projectile.rotation = Projectile.velocity.ToRotation() + MathHelper.PiOver2;
			Lighting.AddLight(Projectile.Center, new Vector3(0.5f, 0.7f, 1f));

			if (Main.rand.NextBool(2)) {
				Dust d = Dust.NewDustDirect(Projectile.position, Projectile.width, Projectile.height, DustID.Electric);
				d.noGravity = true;
				d.velocity *= 0.3f;
				d.scale = 0.8f;
			}
		}
	}
}

using Microsoft.Xna.Framework;
using System;
using Terraria;
using Terraria.ID;
using Terraria.ModLoader;

namespace SummonerReborn.Content.Projectiles
{
	// Bază comună pentru toți minionii zburători din mod (Licurici, Iele, Strigoi, Norișor).
	// Construită după ExampleSimpleMinion din ExampleMod-ul oficial.
	// Fiecare minion schimbă doar ce îl face diferit: viteza, lumina, praful, sau un atac special.
	// Clasele „abstract” nu sunt încărcate de tModLoader ca iteme/proiectile proprii, sunt doar șabloane.
	public abstract class MinionZburatorBaza : ModProjectile
	{
		// Buff-ul care ține minionul în viață (fiecare minion are buff-ul lui).
		protected abstract int BuffMinion { get; }

		// Setări pe care minionii le pot schimba
		protected virtual int Cadre => 4;                       // câte cadre de animație are imaginea
		protected virtual int Latime => 16;
		protected virtual int Inaltime => 16;
		protected virtual float VitezaAtac => 10f;
		protected virtual float Inertie => 18f;                 // cât de „greu” se întoarce (mai mic = mai agil)
		protected virtual Vector3 Lumina => Vector3.Zero;       // culoarea luminii (0 = fără lumină)
		protected virtual int Praf => -1;                       // tipul de praf lăsat în urmă (-1 = deloc)
		protected virtual bool AtacPrinContact => true;         // false = minionul trage, nu lovește cu corpul
		protected virtual int TimpIntreLoviri => 20;            // cât așteaptă până lovește iar același inamic

		// Unde vrea să stea minionul când atacă (pentru contact: chiar pe țintă).
		protected virtual Vector2 PozitieAtac(Vector2 tinta) => tinta;

		// Apelat în fiecare frame cât timp are o țintă (folosit de minionii care trag).
		protected virtual void Ataca(Vector2 tinta, float distanta) { }

		public override void SetStaticDefaults() {
			Main.projFrames[Type] = Cadre;
			ProjectileID.Sets.MinionTargettingFeature[Type] = true; // poți alege ținta cu click dreapta
			Main.projPet[Type] = true;
			ProjectileID.Sets.MinionSacrificable[Type] = true;
			ProjectileID.Sets.CultistIsResistantTo[Type] = true;
		}

		public override void SetDefaults() {
			Projectile.width = Latime;
			Projectile.height = Inaltime;
			Projectile.tileCollide = false;
			Projectile.friendly = true;
			Projectile.minion = true;
			Projectile.DamageType = DamageClass.Summon;
			Projectile.minionSlots = 1f;
			Projectile.penetrate = -1;
			Projectile.usesLocalNPCImmunity = true;
			Projectile.localNPCHitCooldown = TimpIntreLoviri;
		}

		public override bool? CanCutTiles() => false;

		public override bool MinionContactDamage() => AtacPrinContact;

		public override void AI() {
			Player owner = Main.player[Projectile.owner];

			if (!CheckActive(owner))
				return;

			GeneralBehavior(owner, out Vector2 vectorToIdlePosition, out float distanceToIdlePosition);
			SearchForTargets(owner, out bool foundTarget, out float distanceFromTarget, out Vector2 targetCenter);
			Movement(foundTarget, targetCenter, distanceToIdlePosition, vectorToIdlePosition);

			if (foundTarget)
				Ataca(targetCenter, distanceFromTarget);

			Visuals();
		}

		// Minionul trăiește cât timp jucătorul e viu și are buff-ul.
		private bool CheckActive(Player owner) {
			if (owner.dead || !owner.active) {
				owner.ClearBuff(BuffMinion);
				return false;
			}

			if (owner.HasBuff(BuffMinion))
				Projectile.timeLeft = 2;

			return true;
		}

		// Unde stă când n-are ce ataca: deasupra și în spatele jucătorului.
		private void GeneralBehavior(Player owner, out Vector2 vectorToIdlePosition, out float distanceToIdlePosition) {
			Vector2 idlePosition = owner.Center;
			idlePosition.Y -= 48f;
			idlePosition.X += (10 + Projectile.minionPos * 40) * -owner.direction;

			vectorToIdlePosition = idlePosition - Projectile.Center;
			distanceToIdlePosition = vectorToIdlePosition.Length();

			// Dacă a rămas prea departe, se teleportează la jucător.
			if (Main.myPlayer == owner.whoAmI && distanceToIdlePosition > 2000f) {
				Projectile.position = idlePosition;
				Projectile.velocity *= 0.1f;
				Projectile.netUpdate = true;
			}

			// Minionii se împing ușor unul pe altul, ca să nu stea unul peste altul.
			const float overlapVelocity = 0.04f;
			foreach (var other in Main.ActiveProjectiles) {
				if (other.whoAmI != Projectile.whoAmI && other.owner == Projectile.owner && Math.Abs(Projectile.position.X - other.position.X) + Math.Abs(Projectile.position.Y - other.position.Y) < Projectile.width) {
					Projectile.velocity.X += Projectile.position.X < other.position.X ? -overlapVelocity : overlapVelocity;
					Projectile.velocity.Y += Projectile.position.Y < other.position.Y ? -overlapVelocity : overlapVelocity;
				}
			}
		}

		// Alege ținta: întâi ținta aleasă de jucător (sau boss-ul, vezi InvocatorPlayer), altfel cel mai apropiat inamic.
		private void SearchForTargets(Player owner, out bool foundTarget, out float distanceFromTarget, out Vector2 targetCenter) {
			distanceFromTarget = 700f;
			targetCenter = Projectile.position;
			foundTarget = false;

			if (owner.HasMinionAttackTargetNPC) {
				NPC npc = Main.npc[owner.MinionAttackTargetNPC];
				float between = Vector2.Distance(npc.Center, Projectile.Center);
				if (between < 2000f) {
					distanceFromTarget = between;
					targetCenter = npc.Center;
					foundTarget = true;
				}
			}

			if (!foundTarget) {
				foreach (var npc in Main.ActiveNPCs) {
					if (!npc.CanBeChasedBy())
						continue;

					float between = Vector2.Distance(npc.Center, Projectile.Center);
					bool closest = Vector2.Distance(Projectile.Center, targetCenter) > between;
					bool inRange = between < distanceFromTarget;
					bool lineOfSight = Collision.CanHitLine(Projectile.position, Projectile.width, Projectile.height, npc.position, npc.width, npc.height);
					bool closeThroughWall = between < 100f;

					if (((closest && inRange) || !foundTarget) && (lineOfSight || closeThroughWall)) {
						distanceFromTarget = between;
						targetCenter = npc.Center;
						foundTarget = true;
					}
				}
			}

			// Lovește prin contact doar când are țintă (ca să nu lovească manechinele cât stă degeaba).
			Projectile.friendly = foundTarget && AtacPrinContact;
		}

		private void Movement(bool foundTarget, Vector2 targetCenter, float distanceToIdlePosition, Vector2 vectorToIdlePosition) {
			float speed = VitezaAtac;
			float inertia = Inertie;

			if (foundTarget) {
				Vector2 destinatie = PozitieAtac(targetCenter);
				if (Vector2.Distance(destinatie, Projectile.Center) > 40f) {
					Vector2 direction = destinatie - Projectile.Center;
					direction.Normalize();
					direction *= speed;
					Projectile.velocity = (Projectile.velocity * (inertia - 1) + direction) / inertia;
				}
			}
			else {
				if (distanceToIdlePosition > 600f) {
					speed = 12f;
					inertia = 60f;
				}
				else {
					speed = 4f;
					inertia = 80f;
				}

				if (distanceToIdlePosition > 20f) {
					vectorToIdlePosition.Normalize();
					vectorToIdlePosition *= speed;
					Projectile.velocity = (Projectile.velocity * (inertia - 1) + vectorToIdlePosition) / inertia;
				}
				else if (Projectile.velocity == Vector2.Zero) {
					Projectile.velocity.X = -0.15f;
					Projectile.velocity.Y = -0.05f;
				}
			}
		}

		private void Visuals() {
			Projectile.rotation = Projectile.velocity.X * 0.05f;

			// Animația: trece prin cadre, pe rând.
			if (++Projectile.frameCounter >= 5) {
				Projectile.frameCounter = 0;
				Projectile.frame = (Projectile.frame + 1) % Main.projFrames[Type];
			}

			if (Lumina != Vector3.Zero)
				Lighting.AddLight(Projectile.Center, Lumina);

			if (Praf >= 0 && Main.rand.NextBool(10)) {
				Dust d = Dust.NewDustDirect(Projectile.position, Projectile.width, Projectile.height, Praf);
				d.noGravity = true;
				d.velocity *= 0.2f;
			}
		}
	}
}

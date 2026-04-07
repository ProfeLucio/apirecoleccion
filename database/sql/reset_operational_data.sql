BEGIN;

CREATE EXTENSION IF NOT EXISTS pgcrypto;

TRUNCATE TABLE posiciones, recorridos, ruta_calle, vehiculos, rutas, perfiles RESTART IDENTITY CASCADE;

DO $$
DECLARE
    required_street_count integer := 12;
    available_street_count integer;
BEGIN
    SELECT COUNT(*) INTO available_street_count FROM calles;

    IF available_street_count < required_street_count THEN
        RAISE EXCEPTION 'Se requieren al menos % calles y solo existen %.', required_street_count, available_street_count;
    END IF;
END $$;

CREATE TEMP TABLE tmp_perfiles AS
SELECT
    gen_random_uuid() AS id,
    profile_number,
    format('Nombre Grupo %s', profile_number) AS nombre_perfil
FROM generate_series(1, 20) AS profile_number;

INSERT INTO perfiles (id, nombre_perfil, created_at, updated_at)
SELECT id, nombre_perfil, NOW(), NOW()
FROM tmp_perfiles;

CREATE TEMP TABLE tmp_route_templates (
    route_position integer,
    nombre_ruta text,
    color_hex text,
    start_rn integer,
    end_rn integer
);

INSERT INTO tmp_route_templates (route_position, nombre_ruta, color_hex, start_rn, end_rn)
VALUES
    (1, 'Ruta 1', '#D1495B', 1, 4),
    (2, 'Ruta 2', '#2E86AB', 5, 8),
    (3, 'Ruta 3', '#3D9970', 9, 12);

CREATE TEMP TABLE tmp_ordered_calles AS
SELECT
    id,
    ROW_NUMBER() OVER (ORDER BY nombre, id) AS rn
FROM calles;

CREATE TEMP TABLE tmp_route_template_calles AS
SELECT
    t.route_position,
    t.nombre_ruta,
    t.color_hex,
    c.id AS calle_id,
    ROW_NUMBER() OVER (PARTITION BY t.route_position ORDER BY c.rn) - 1 AS orden
FROM tmp_route_templates t
JOIN tmp_ordered_calles c
    ON c.rn BETWEEN t.start_rn AND t.end_rn;

CREATE TEMP TABLE tmp_routes AS
SELECT
    gen_random_uuid() AS id,
    p.id AS perfil_id,
    p.profile_number,
    t.route_position,
    t.nombre_ruta,
    t.color_hex
FROM tmp_perfiles p
CROSS JOIN tmp_route_templates t;

INSERT INTO rutas (id, perfil_id, nombre_ruta, color_hex, shape, created_at, updated_at)
SELECT
    r.id,
    r.perfil_id,
    r.nombre_ruta,
    r.color_hex,
    ST_Multi(ST_Collect(c.shape ORDER BY rtc.orden)),
    NOW(),
    NOW()
FROM tmp_routes r
JOIN tmp_route_template_calles rtc
    ON rtc.route_position = r.route_position
JOIN calles c
    ON c.id = rtc.calle_id
GROUP BY r.id, r.perfil_id, r.nombre_ruta, r.color_hex;

INSERT INTO ruta_calle (ruta_id, calle_id, orden)
SELECT
    r.id,
    rtc.calle_id,
    rtc.orden
FROM tmp_routes r
JOIN tmp_route_template_calles rtc
    ON rtc.route_position = r.route_position;

INSERT INTO vehiculos (id, placa, marca, modelo, activo, perfil_id, created_at, updated_at)
SELECT
    gen_random_uuid(),
    format('%s-%s', v.prefijo, LPAD(p.profile_number::text, 2, '0')),
    'Chevrolet',
    '2024',
    true,
    p.id,
    NOW(),
    NOW()
FROM tmp_perfiles p
CROSS JOIN (
    VALUES ('AAA'), ('BBB'), ('CCC'), ('DDD'), ('EEE')
) AS v(prefijo);

COMMIT;

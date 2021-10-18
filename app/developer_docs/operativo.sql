--
-- PostgreSQL database dump
--

-- Dumped from database version 12.2 (Ubuntu 12.2-4)
-- Dumped by pg_dump version 12.2 (Ubuntu 12.2-4)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Data for Name: operativo; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.operativo VALUES ('censo2010', NULL, 1);
INSERT INTO public.operativo VALUES ('censo2020', NULL, 2);
INSERT INTO public.operativo VALUES ('vs2013', 'censo2010actualiz2013', 3);


--
-- Name: operativo_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.operativo_id_seq', 1, false);


--
-- PostgreSQL database dump complete
--

